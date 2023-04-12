<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\BillieApi\Service;

use Billie\BilliePayment\Components\Order\Model\Extension\OrderExtension;
use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\Order\Util\DocumentUrlHelper;
use Billie\Sdk\Exception\BillieException;
use Billie\Sdk\Model\Order;
use Billie\Sdk\Model\Request\OrderRequestModel;
use Billie\Sdk\Model\Request\ShipOrderRequestModel;
use Billie\Sdk\Service\Request\CancelOrderRequest;
use Billie\Sdk\Service\Request\ShipOrderRequest;
use Monolog\Logger;
use Shopware\Core\Checkout\Document\Renderer\DeliveryNoteRenderer;
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
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DocumentUrlHelper
     */
    private $documentUrlHelper;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * TODO remove interface and increase min. SW Version to 6.5
     * @var EntityRepository|\Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface
     */
    private $orderDataRepository;

    public function __construct(
        ContainerInterface $container,
        $orderDataRepository,
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
                    $invoiceNumber = isset($config['custom']['invoiceNumber']) ? $config['custom']['invoiceNumber'] : null;
                    $invoiceUrl = $this->documentUrlHelper->generateRouteForDocument($document);
                }

                if ($shippingUrl === null &&
                    $document->getDocumentType()->getTechnicalName() === DeliveryNoteRenderer::TYPE
                ) {
                    $shippingUrl = $this->documentUrlHelper->generateRouteForDocument($document);
                }
            }
        }

        $data = new ShipOrderRequestModel($billieData->getReferenceId());
        $data->setInvoiceNumber($invoiceNumber)
            ->setInvoiceUrl($invoiceUrl ?? '.')
            ->setShippingDocumentUrl($shippingUrl);

        try {
            /** @var \Billie\Sdk\Model\Order $response */
            $response = $this->container->get(ShipOrderRequest::class)->execute($data);
            $this->updateOrderState($billieData, $response->getState());

            return true;
        } catch (BillieException $e) {
            $this->logger->critical(
                'Exception during shipment. (Exception: ' . $e->getMessage() . ')',
                [
                    'error' => $e->getBillieCode(),
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

        if ($billieData->getOrderState() === Order::STATE_CANCELLED) {
            return true;
        }

        try {
            $this->container->get(CancelOrderRequest::class)->execute(new OrderRequestModel($billieData->getReferenceId()));
            $this->updateOrderState($billieData, Order::STATE_CANCELLED);

            return true;
        } catch (BillieException $e) {
            $this->logger->critical(
                'Exception during cancellation. (Exception: ' . $e->getMessage() . ')',
                [
                    'error' => $e->getBillieCode(),
                    'order' => $order->getId(),
                    'billie-reference-id' => $billieData->getReferenceId(),
                ]
            );
        }

        return false;
    }

    private function updateOrderState(OrderDataEntity $billieData, string $state): void
    {
        try {
            $this->orderDataRepository->update([
                [
                    OrderDataEntity::FIELD_ID => $billieData->getId(),
                    OrderDataEntity::FIELD_ORDER_STATE => $state,
                ],
            ], Context::createDefaultContext());
        } catch (\Exception $e) {
            $this->logger->critical(
                'Order state can not be updated. (Exception: ' . $e->getMessage() . ')',
                [
                    'code' => $e->getCode(),
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
            throw new \RuntimeException('The order `' . $order->getId() . '` is not a billie order, or the billie order data extension has not been loaded');
        }

        return $billieData;
    }
}
