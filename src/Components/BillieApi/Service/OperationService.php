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

/**
 * @internal please always use the state-machine to change the state of the order. If you know what you are doing, keep going
 */
class OperationService
{
    private Logger $logger;

    private DocumentUrlHelper $documentUrlHelper;

    private ContainerInterface $container;

    /**
     * @var EntityRepository<OrderDataCollection>
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $orderDataRepository;

    public function __construct(
        ContainerInterface $container,
        object $orderDataRepository,
        DocumentUrlHelper $documentUrlHelper,
        Logger $logger
    ) {
        $this->container = $container;
        $this->logger = $logger;
        $this->documentUrlHelper = $documentUrlHelper;
        $this->orderDataRepository = $orderDataRepository;
    }

    public function ship(OrderEntity $order): bool
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

            $order = $this->container->get(GetOrderRequest::class)->execute(new OrderRequestModel($billieData->getReferenceId()));
            $this->updateOrderState($billieData, $order->getState(), [
                OrderDataEntity::FIELD_INVOICE_UUID => $invoiceResponse->getUuid(),
                OrderDataEntity::FIELD_EXTERNAL_INVOICE_NUMBER => $invoiceNumber,
            ]);

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

    public function cancel(OrderEntity $order): bool
    {
        $billieData = $this->getBillieDataForOrder($order);

        if ($billieData->getInvoiceUuid() !== null) {
            return $this->return($order);
        }

        if ($billieData->getOrderState() === Order::STATE_CANCELLED) {
            return true;
        }

        if ($billieData->getOrderState() !== Order::STATE_CREATED) {
            return false;
        }

        try {
            $this->container->get(CancelOrderRequest::class)->execute(new OrderRequestModel($billieData->getReferenceId()));
            $this->updateOrderState($billieData, Order::STATE_CANCELLED);

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

    public function return(OrderEntity $order): bool
    {
        $billieData = $this->getBillieDataForOrder($order);

        if ($billieData->getInvoiceUuid() === null) {
            return $this->cancel($order);
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
            $this->updateOrderState($billieData, Order::STATE_CANCELLED);

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

    private function updateOrderState(OrderDataEntity $billieData, string $state, array $additionalData = []): void
    {
        try {
            $this->orderDataRepository->update([
                array_merge([
                    OrderDataEntity::FIELD_ID => $billieData->getId(),
                    OrderDataEntity::FIELD_ORDER_VERSION_ID => $billieData->getVersionId(),
                    OrderDataEntity::FIELD_ORDER_STATE => $state,
                ], $additionalData),
            ], Context::createDefaultContext());
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
