<?php


namespace Billie\BilliePayment\Components\PaymentMethod\Util;


use Billie\BilliePayment\Bootstrap\PaymentMethods;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class MethodHelper
{

    public static function isBilliePayment(PaymentMethodEntity $paymentMethodEntity): bool
    {
        return array_key_exists($paymentMethodEntity->getHandlerIdentifier(), PaymentMethods::PAYMENT_METHODS);
    }

}
