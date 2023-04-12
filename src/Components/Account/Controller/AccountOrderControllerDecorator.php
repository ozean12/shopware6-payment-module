<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Account\Controller;

use Billie\BilliePayment\Components\Order\Model\Extension\OrderExtension;
use Billie\BilliePayment\Components\Order\Model\OrderDataEntity;
use Billie\BilliePayment\Components\PaymentMethod\Util\MethodHelper;
use Billie\BilliePayment\Util\CriteriaHelper;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\AccountOrderController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class AccountOrderControllerDecorator extends AccountOrderController
{
    /**
     * @Route("/account/order/update/{orderId}", name="frontend.account.edit-order.update-order", methods={"POST"})
     * @noinspection NullPointerExceptionInspection
     */
    public function updateOrder(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        $order = $this->fetchOrder($context->getContext(), $orderId);
        /** @var OrderDataEntity|null $billieData */
        $billieData = $order ? $order->getExtension(OrderExtension::EXTENSION_NAME) : null;

        $paymentMethod = $order->getTransactions()->first()->getPaymentMethod();
        if ($billieData && MethodHelper::isBilliePayment($paymentMethod) && $billieData->isSuccessful()) {
            // You can't change the payment if it is a billie order
            return $this->redirectToRoute('frontend.account.edit-order.page', ['orderId' => $orderId]);
        }

        return parent::updateOrder($orderId, $request, $context);
    }

    protected function fetchOrder(Context $context, string $orderId): ?OrderEntity
    {
        $orderRepository = $this->container->get('order.repository');

        return $orderRepository->search(CriteriaHelper::getCriteriaForOrder($orderId), $context)->first();
    }
}
