<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\PaymentMethod\Util;

use Billie\BilliePayment\Components\PaymentMethod\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class MethodHelper
{
    public static function isBilliePayment(PaymentMethodEntity $paymentMethodEntity): bool
    {
        return is_subclass_of($paymentMethodEntity->getHandlerIdentifier(), AbstractPaymentHandler::class);
    }
}
