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
use Billie\BilliePayment\Components\StateMachine\Event\BillieStateChangedEvent;
use Billie\Sdk\Exception\BillieException;
use Billie\Sdk\Exception\GatewayException;
use Billie\Sdk\Model\Order;
use Billie\Sdk\Service\Request\CheckoutSession\CheckoutSessionConfirmRequest;
use Monolog\Logger;
use RuntimeException;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Throwable;

class PaymentHandler implements SynchronousPaymentHandlerInterface
{
    /**
     * @param EntityRepository<OrderDataCollection> $orderDataRepository
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfirmDataService $confirmDataService,
        private readonly EntityRepository $orderDataRepository,
        private readonly Logger $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
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
            throw $this->syncProcessInterrupted($transaction->getOrderTransaction()->getId(), 'unknown error during payment');
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

            throw $this->syncProcessInterrupted($transaction->getOrderTransaction()->getId(), $billieException->getMessage(), $billieException);
        }

        $this->eventDispatcher->dispatch(new BillieStateChangedEvent($order, $transaction->getOrderTransaction(), Order::STATE_AUTHORIZED, $salesChannelContext->getContext()));
    }

    private function syncProcessInterrupted(string $orderTransactionId, string $errorMessage, ?Throwable $e = null): Throwable
    {
        if (class_exists(PaymentException::class)) {
            return PaymentException::syncProcessInterrupted($orderTransactionId, $errorMessage, $e);
        } elseif (class_exists(SyncPaymentProcessException::class)) {
            // required for shopware version <= 6.5.3
            return new SyncPaymentProcessException($orderTransactionId, $errorMessage, $e); // @phpstan-ignore-line
        }

        // should never occur - just to be safe
        return new RuntimeException('payment interrupted: ' . $errorMessage, 0, $e);
    }
}
