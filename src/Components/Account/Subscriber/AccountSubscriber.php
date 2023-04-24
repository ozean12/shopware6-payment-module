<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Account\Subscriber;

use Billie\BilliePayment\Components\PaymentMethod\Util\MethodHelper;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AccountSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            HandlePaymentMethodRouteRequestEvent::class => 'onHandlePaymentMethodRouteRequest',
            AccountEditOrderPageLoadedEvent::class => 'onAccountEditOrderPageLoaded',
        ];
    }

    public function onHandlePaymentMethodRouteRequest(HandlePaymentMethodRouteRequestEvent $event): void
    {
        if ($event->getStorefrontRequest()->request->has('billie_payment')) {
            $event->getStoreApiRequest()->request->set(
                'billie_payment',
                $event->getStorefrontRequest()->request->get('billie_payment')
            );
        }
    }

    public function onAccountEditOrderPageLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        $page = $event->getPage();

        $paymentMethods = $page->getPaymentMethods()->filter(static fn(PaymentMethodEntity $paymentMethod): bool => !MethodHelper::isBilliePayment($paymentMethod));

        $page->setPaymentMethods($paymentMethods);
    }
}
