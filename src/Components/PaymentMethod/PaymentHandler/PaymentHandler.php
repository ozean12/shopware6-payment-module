<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PaymentMethod\PaymentHandler;

use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\PaymentMethod\Service\ConfirmDataService;
use Billie\Sdk\Exception\BillieException;
use Billie\Sdk\Model\Request\UpdateOrderRequestModel;
use Billie\Sdk\Service\Request\CheckoutSessionConfirmRequest;
use Billie\Sdk\Service\Request\UpdateOrderRequest;
use Monolog\Logger;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class PaymentHandler implements SynchronousPaymentHandlerInterface
{
    /**
     * @var ConfirmDataService
     */
    private $confirmDataService;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderDataRepository;

    /**
     * @var ContainerInterface
     */
    private $requestServiceLocator;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        ContainerInterface $requestServiceLocator,
        ConfirmDataService $confirmDataService,
        EntityRepositoryInterface $orderDataRepository,
        Logger $logger
    ) {
        $this->confirmDataService = $confirmDataService;
        $this->orderDataRepository = $orderDataRepository;
        $this->requestServiceLocator = $requestServiceLocator;
        $this->logger = $logger;
    }

    public function pay(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): void {
        /** @var ParameterBag $billieData */
        $billieData = $dataBag->get('billie_payment', new ParameterBag([]));

        $order = $transaction->getOrder();

        if ($billieData->count() === 0 || $billieData->has('session-id') === false) {
            throw new SyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'unknown error during payment');
        }

        $confirmModel = $this->confirmDataService->getConfirmModel($billieData->get('session-id'), $order);
        try {
            /** @noinspection NullPointerExceptionInspection */
            $response = $this->requestServiceLocator->get(CheckoutSessionConfirmRequest::class)->execute($confirmModel);

            $this->orderDataRepository->upsert([
                [
                    OrderDataEntity::FIELD_ORDER_ID => $order->getId(),
                    OrderDataEntity::FIELD_ORDER_VERSION_ID => $order->getVersionId(),
                    OrderDataEntity::FIELD_REFERENCE_ID => $response->getUuid(),
                    OrderDataEntity::FIELD_IS_SUCCESSFUL => true,
                ],
            ], $salesChannelContext->getContext());
        } catch (BillieException $exception) {
            $this->logger->addCritical(
                'Exception during checkout session confirmation. (Exception: ' . $exception->getMessage() . ')',
                [
                    'error' => $exception->getBillieCode(),
                    'order' => $order->getId(),
                    'session-id' => $billieData->get('session-id'),
                ]
            );

            throw new SyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }

        $updateOrderModel = (new UpdateOrderRequestModel($response->getUuid()))
            ->setOrderId($order->getOrderNumber());
        try {
            /** @noinspection NullPointerExceptionInspection */
            $this->requestServiceLocator->get(UpdateOrderRequest::class)->execute($updateOrderModel);
        } catch (BillieException $exception) {
            $this->logger->addCritical(
                'Exception during order update. (Exception: ' . $exception->getMessage() . ')',
                [
                    'error' => $exception->getBillieCode(),
                    'order' => $order->getId(),
                    'billie-reference-id' => $response->getUuid(),
                ]
            );
        }
    }
}
