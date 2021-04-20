<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Components\StateMachine\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class InvoiceNumberMissingException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct(
            'It is not allowed to change the delivery status if the billie invoice number is missing!'
        );
    }

    public function getErrorCode(): string
    {
        return 'BILLIE__INVOICE_NUMBER_MISSING';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
