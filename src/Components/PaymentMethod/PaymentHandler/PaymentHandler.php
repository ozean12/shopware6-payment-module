<?php

namespace Billie\BilliePayment\Components\PaymentMethod\PaymentHandler;


use Billie\BilliePayment\Components\RedirectException\Exception\ForwardException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class PaymentHandler implements SynchronousPaymentHandlerInterface
{
    public const ERROR_SNIPPET_VIOLATION_PREFIX = 'VIOLATION::';

    public function pay(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): void
    {
        /** @var ParameterBag $billieData */
        $billieData = $dataBag->get('billie', new ParameterBag([]));

        $order = $this->getOrderWithAssociations($transaction->getOrder(), $salesChannelContext->getContext());

        if ($order === null || $billieData->count() === 0) {
            throw new SyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'unknown error during payment');
        }
        try {
        } catch (\Exception $e) {
            throw new ForwardException(
                'frontend.account.edit-order.page',
                ['orderId' => $order->getId()],
                ['billie-errors' => [$e->getMessage()]],
                new SyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $e->getMessage())
            );
        }
    }

    /**
     * @return OrderEntity|null
     */
    protected function getOrderWithAssociations(OrderEntity $order, Context $context): OrderEntity
    {
        return $this->orderRepository->search(CriteriaHelper::getCriteriaForOrder($order->getId()), $context)->first();
    }

    /**
     * @param OrderEntity|SalesChannelContext $baseData
     */
    public function getValidationDefinitions(Request $request, $baseData): array
    {
        $validations = [
        ];
        return $validations;
    }
}
