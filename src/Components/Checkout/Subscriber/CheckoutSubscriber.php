<?php declare(strict_types=1);

namespace Billie\BilliePayment\Components\Checkout\Subscriber;

use Billie\BilliePayment\Components\Checkout\Service\WidgetService;
use Billie\BilliePayment\Components\PaymentMethod\Util\MethodHelper;
use Ratepay\RpayPayments\Components\PaymentHandler\AbstractPaymentHandler;
use Ratepay\RpayPayments\Util\DataValidationHelper;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{

    /**
     * @var WidgetService
     */
    private $widgetService;

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
        $paymentMethod = $event->getSalesChannelContext()->getPaymentMethod();
        if (MethodHelper::isBilliePayment($paymentMethod) && $event->getPage()->getPaymentMethods()->has($paymentMethod->getId())) {
            if ($event instanceof CheckoutConfirmPageLoadedEvent) {
                $widgetData = $this->widgetService->getWidgetDataBySalesChannelContext($event->getSalesChannelContext());
            } else if ($event instanceof AccountEditOrderPageLoadedEvent) {
                $widgetData = $this->widgetService->getWidgetDataByOrder($event->getPage()->getOrder(), $event->getSalesChannelContext());
            } else {
                throw new \RuntimeException('invalid event: ' . gettype($event));
            }

            if ($widgetData) {
                $extension = $event->getPage()->getExtension('billie_payment') ?? new ArrayStruct();
                $extension->set('widget', $widgetData->all());

                $event->getPage()->addExtension('billie_payment', $extension);
            }
        }
    }
}
