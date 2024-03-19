<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PaymentMethod\PaymentHandler;

use Billie\BilliePayment\Components\Order\Model\Collection\OrderDataCollection;
use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\PaymentMethod\Service\ConfirmDataService;
use Billie\Sdk\Exception\BillieException;
use Billie\Sdk\Exception\GatewayException;
use Billie\Sdk\Service\Request\CheckoutSession\CheckoutSessionConfirmRequest;
use Monolog\Logger;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class AbstractPaymentHandler implements SynchronousPaymentHandlerInterface
{
    private ConfirmDataService $confirmDataService;

    /**
     * @var EntityRepository<OrderDataCollection>
     * the interface has been deprecated, but shopware is using the Interface in a decorator for the repository.
     * so it will crash, if we are only using EntityRepository, cause an object of the decorator got injected into the constructor.
     * After Shopware has removed the decorator, we can replace this by a normal definition
     * TODO remove comment on Shopware Version 6.5.0.0 & readd type hint & change constructor argument type
     */
    private object $orderDataRepository;

    private ContainerInterface $container;

    private Logger $logger;

    public function __construct(
        ContainerInterface $container,
        ConfirmDataService $confirmDataService,
        object $orderDataRepository,
        Logger $logger
    ) {
        $this->confirmDataService = $confirmDataService;
        $this->orderDataRepository = $orderDataRepository;
        $this->container = $container;
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

        if ($billieData->count() === 0 || !$billieData->has('session-id')) {
            throw new SyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'unknown error during payment');
        }

        $confirmModel = $this->confirmDataService->getConfirmModel($billieData->get('session-id'), $order);
        try {
            $response = $this->container->get(CheckoutSessionConfirmRequest::class)->execute($confirmModel);

            $this->orderDataRepository->upsert([
                [
                    OrderDataEntity::FIELD_ORDER_ID => $order->getId(),
                    OrderDataEntity::FIELD_ORDER_VERSION_ID => $order->getVersionId(),
                    OrderDataEntity::FIELD_REFERENCE_ID => $response->getUuid(),
                    OrderDataEntity::FIELD_ORDER_STATE => $response->getState(),
                    OrderDataEntity::FIELD_BANK_IBAN => $response->getPaymentMethods()[0]->getIban(),
                    OrderDataEntity::FIELD_BANK_BIC => $response->getPaymentMethods()[0]->getBic(),
                    OrderDataEntity::FIELD_BANK_NAME => $response->getPaymentMethods()[0]->getBankName(),
                    OrderDataEntity::FIELD_DURATION => $response->getDuration(),
                    OrderDataEntity::FIELD_IS_SUCCESSFUL => true,
                ],
            ], $salesChannelContext->getContext());
        } catch (BillieException $billieException) {
            $context = [
                'error' => $billieException->getBillieCode(),
                'order' => $order->getId(),
                'session-id' => $billieData->get('session-id'),
                'request' => $billieException,
            ];
            if ($billieException instanceof GatewayException) {
                $context['request'] = $billieException->getRequestData();
                $context['response'] = $billieException->getResponseData();
            }

            $this->logger->critical(
                'Exception during checkout session confirmation. (Exception: ' . $billieException->getMessage() . ')',
                $context
            );

            throw new SyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $billieException->getMessage());
        }
    }
}
