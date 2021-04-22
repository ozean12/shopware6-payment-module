<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\Checkout\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class PaymentMethodNotAllowedException extends ShopwareHttpException
{
    public function __construct(string $paymentMethodName)
    {
        parent::__construct(
            'You are not allowed to change the payment method to "{{ paymentMethodName }}".',
            ['paymentMethodName' => $paymentMethodName]
        );
    }

    public function getErrorCode(): string
    {
        return 'BILLIE__PAYMENT_METHOD_NOT_ALLOWED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
