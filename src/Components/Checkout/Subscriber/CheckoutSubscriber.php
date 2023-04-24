<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Checkout\Subscriber;

use Billie\BilliePayment\Components\Checkout\Service\WidgetService;
use Billie\BilliePayment\Components\PaymentMethod\Util\MethodHelper;
use RuntimeException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private WidgetService $widgetService;

    public function __construct(WidgetService $widgetService)
    {
        $this->widgetService = $widgetService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => ['addWidgetData', 310],
            AccountEditOrderPageLoadedEvent::class => ['addWidgetData', 310],
        ];
    }

    public function addWidgetData(PageLoadedEvent $event): void
    {
        if (!$event instanceof CheckoutConfirmPageLoadedEvent && !$event instanceof AccountEditOrderPageLoadedEvent) {
            throw new RuntimeException('method ' . self::class . '::' . __METHOD__ . ' does not supports a parameter of type' . get_class($event));
        }

        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        if (MethodHelper::isBilliePayment($paymentMethod) && $event->getPage()->getPaymentMethods()->has($paymentMethod->getId())) {
            if ($event instanceof CheckoutConfirmPageLoadedEvent) {
                $widgetData = $this->widgetService->getWidgetDataBySalesChannelContext($event->getSalesChannelContext());
            } elseif ($event instanceof AccountEditOrderPageLoadedEvent) {
                $widgetData = $this->widgetService->getWidgetDataByOrder($event->getPage()->getOrder(), $event->getSalesChannelContext());
            } else {
                throw new RuntimeException('invalid event: ' . gettype($event));
            }

            if ($widgetData instanceof ArrayStruct) {
                /** @var ArrayStruct $extension */
                $extension = $event->getPage()->getExtension('billie_payment') ?? new ArrayStruct();
                $extension->set('widget', $widgetData->all());

                $event->getPage()->addExtension('billie_payment', $extension);
            }
        }
    }
}
