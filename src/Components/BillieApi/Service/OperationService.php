<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\BillieApi\Service;

use Billie\BilliePayment\Components\Order\Model\Collection\OrderDataCollection;
use Billie\BilliePayment\Components\Order\Model\Extension\OrderExtension;
use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\Order\Util\DocumentUrlHelper;
use Billie\BilliePayment\Components\StateMachine\Event\BillieStateChangedEvent;
use Billie\Sdk\Exception\BillieException;
use Billie\Sdk\Model\Amount;
use Billie\Sdk\Model\Order;
use Billie\Sdk\Model\Request\Invoice\CreateCreditNoteRequestModel;
use Billie\Sdk\Model\Request\Invoice\CreateInvoiceRequestModel;
use Billie\Sdk\Model\Request\OrderRequestModel;
use Billie\Sdk\Service\Request\Invoice\CreateCreditNoteRequest;
use Billie\Sdk\Service\Request\Invoice\CreateInvoiceRequest;
use Billie\Sdk\Service\Request\Order\CancelOrderRequest;
use Billie\Sdk\Service\Request\Order\GetOrderRequest;
use Exception;
use Monolog\Logger;
use RuntimeException;
use Shopware\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal please always use the state-machine to change the state of the order. If you know what you are doing, keep going
 */
class OperationService
{
    /**
     * @param EntityRepository<OrderDataCollection> $orderDataRepository
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityRepository $orderDataRepository,
        private readonly DocumentUrlHelper $documentUrlHelper,
        private readonly Logger $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function ship(OrderEntity $order, Context $context): bool
    {
        $billieData = $this->getBillieDataForOrder($order);

        if ($billieData->getOrderState() === Order::STATE_SHIPPED) {
            return true;
        }

        if ($billieData->getOrderState() !== Order::STATE_CREATED) {
            return false;
        }

        $invoiceNumber = $billieData->getExternalInvoiceNumber();
        $invoiceUrl = $billieData->getExternalInvoiceUrl();
        $shippingUrl = $billieData->getExternalDeliveryNoteUrl();

        if (!$invoiceNumber || !$shippingUrl) {
            foreach ($order->getDocuments() as $document) {
                if ($invoiceNumber === null &&
                    $document->getDocumentType()->getTechnicalName() === InvoiceRenderer::TYPE
                ) {
                    $config = $document->getConfig();
                    $invoiceNumber = $config['custom']['invoiceNumber'] ?? null;
                    $invoiceUrl = $this->documentUrlHelper->generateRouteForDocument($document);
                }
            }
        }

        $data = (new CreateInvoiceRequestModel())
            ->setOrders([$billieData->getReferenceId()])
            ->setInvoiceUrl($invoiceUrl ?? '.')
            ->setInvoiceNumber($invoiceNumber)
            ->setAmount(
                (new Amount())
                    ->setGross($order->getPrice()->getTotalPrice())
                    ->setNet($order->getPrice()->getNetPrice())
            );

        try {
            $invoiceResponse = $this->container->get(CreateInvoiceRequest::class)->execute($data);
            sleep(1); // we have to wait one second, so the invoice has been published into the internal systems of billie.

            $billieOrder = $this->container->get(GetOrderRequest::class)->execute(new OrderRequestModel($billieData->getReferenceId()));
            $this->updateOrderState(
                $order,
                $billieData,
                $billieOrder->getState(),
                $context,
                [
                    OrderDataEntity::FIELD_INVOICE_UUID => $invoiceResponse->getUuid(),
                    OrderDataEntity::FIELD_EXTERNAL_INVOICE_NUMBER => $invoiceNumber,
                ],
            );

            return true;
        } catch (BillieException $billieException) {
            $this->logger->critical(
                'Exception during create invoice. (Exception: ' . $billieException->getMessage() . ')',
                [
                    'error' => $billieException->getBillieCode(),
                    'order' => $order->getId(),
                    'billie-reference-id' => $billieData->getReferenceId(),
                ]
            );
        }

        return false;
    }

    public function cancel(OrderEntity $order, Context $context): bool
    {
        $billieData = $this->getBillieDataForOrder($order);

        if ($billieData->getInvoiceUuid() !== null) {
            return $this->return($order, $context);
        }

        if ($billieData->getOrderState() === Order::STATE_CANCELLED) {
            return true;
        }

        if ($billieData->getOrderState() !== Order::STATE_CREATED) {
            return false;
        }

        try {
            $this->container->get(CancelOrderRequest::class)->execute(new OrderRequestModel($billieData->getReferenceId()));
            $this->updateOrderState($order, $billieData, Order::STATE_CANCELLED, context: $context);

            return true;
        } catch (BillieException $billieException) {
            $this->logger->critical(
                'Exception during cancellation. (Exception: ' . $billieException->getMessage() . ')',
                [
                    'error' => $billieException->getBillieCode(),
                    'order' => $order->getId(),
                    'billie-reference-id' => $billieData->getReferenceId(),
                ]
            );
        }

        return false;
    }

    public function return(OrderEntity $order, Context $context): bool
    {
        $billieData = $this->getBillieDataForOrder($order);

        if ($billieData->getInvoiceUuid() === null) {
            return $this->cancel($order, $context);
        }

        if ($billieData->getOrderState() === Order::STATE_CANCELLED) {
            return true;
        }

        if ($billieData->getOrderState() !== Order::STATE_SHIPPED) {
            return false;
        }

        try {
            $data = (new CreateCreditNoteRequestModel($billieData->getInvoiceUuid(), $billieData->getExternalInvoiceNumber()))
                ->setAmount(
                    (new Amount())
                        ->setGross($order->getPrice()->getTotalPrice())
                        ->setNet($order->getPrice()->getNetPrice())
                );

            $this->container->get(CreateCreditNoteRequest::class)->execute($data);
            $this->updateOrderState($order, $billieData, Order::STATE_CANCELLED, context: $context);

            return true;
        } catch (BillieException $billieException) {
            $this->logger->critical(
                'Exception during cancellation. (Exception: ' . $billieException->getMessage() . ')',
                [
                    'error' => $billieException->getBillieCode(),
                    'order' => $order->getId(),
                    'billie-reference-id' => $billieData->getReferenceId(),
                ]
            );
        }

        return false;
    }

    private function updateOrderState(OrderEntity $orderEntity, OrderDataEntity $billieData, string $state, Context $context, array $additionalData = []): void
    {
        try {
            $this->orderDataRepository->update([
                array_merge([
                    OrderDataEntity::FIELD_ID => $billieData->getId(),
                    OrderDataEntity::FIELD_ORDER_VERSION_ID => $billieData->getVersionId(),
                    OrderDataEntity::FIELD_ORDER_STATE => $state,
                ], $additionalData),
            ], $context);
        } catch (Exception $exception) {
            $this->logger->critical(
                'Order state can not be updated. (Exception: ' . $exception->getMessage() . ')',
                [
                    'code' => $exception->getCode(),
                    'order' => $billieData->getOrderId(),
                    'billie-reference-id' => $billieData->getReferenceId(),
                    'new-status' => $state,
                ]
            );
        }

        $this->eventDispatcher->dispatch(new BillieStateChangedEvent(
            $orderEntity,
            $orderEntity->getTransactions()->last(),
            $state,
            $context
        ));
    }

    private function getBillieDataForOrder(OrderEntity $order): OrderDataEntity
    {
        $billieData = $order->getExtension(OrderExtension::EXTENSION_NAME);

        if (!$billieData instanceof OrderDataEntity) {
            throw new RuntimeException('The order `' . $order->getId() . '` is not a billie order, or the billie order data extension has not been loaded');
        }

        return $billieData;
    }
}
