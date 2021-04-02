<?php

namespace Billie\BilliePayment\Components\PaymentMethod\PaymentHandler;


use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\PaymentMethod\Service\ConfirmDataService;
use Billie\Sdk\Exception\BillieException;
use Billie\Sdk\Service\Request\CheckoutSessionConfirmRequest;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\ParameterBag;

class PaymentHandler implements SynchronousPaymentHandlerInterface
{
    /**
     * @var CheckoutSessionConfirmRequest
     */
    private $checkoutSessionConfirmRequest;
    /**
     * @var ConfirmDataService
     */
    private $confirmDataService;
    /**
     * @var EntityRepositoryInterface
     */
    private $orderDataRepository;

    public function __construct(
        ConfirmDataService $confirmDataService,
        CheckoutSessionConfirmRequest $checkoutSessionConfirmRequest,
        EntityRepositoryInterface $orderDataRepository
    )
    {
        $this->checkoutSessionConfirmRequest = $checkoutSessionConfirmRequest;
        $this->confirmDataService = $confirmDataService;
        $this->orderDataRepository = $orderDataRepository;
    }

    public function pay(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): void
    {
        /** @var ParameterBag $billieData */
        $billieData = $dataBag->get('billie_payment', new ParameterBag([]));

        $order = $transaction->getOrder();

        if ($billieData->count() === 0 || $billieData->has('session-id') === false) {
            throw new SyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'unknown error during payment');
        }

        $confirmModel = $this->confirmDataService->getConfirmModel($billieData->get('session-id'), $order);
        try {
            $response = $this->checkoutSessionConfirmRequest->execute($confirmModel);

            $this->orderDataRepository->upsert([
                [
                    OrderDataEntity::FIELD_ORDER_ID => $order->getId(),
                    OrderDataEntity::FIELD_ORDER_VERSION_ID => $order->getVersionId(),
                    OrderDataEntity::FIELD_REFERENCE_ID => $response->getUuid(),
                    OrderDataEntity::FIELD_IS_SUCCESSFUL => true
                ]
            ], $salesChannelContext->getContext());

        } catch (BillieException $exception) {
            throw new SyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $exception->getMessage());
        }
    }
}
