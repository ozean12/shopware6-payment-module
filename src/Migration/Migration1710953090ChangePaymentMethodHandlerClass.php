<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Migration;

use Billie\BilliePayment\Components\PaymentMethod\PaymentHandler\InvoicePaymentHandler;
use Billie\BilliePayment\Util\MigrationHelper;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1710953090ChangePaymentMethodHandlerClass extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1710953090;
    }

    public function update(Connection $connection): void
    {
        $connection->update(
            PaymentMethodDefinition::ENTITY_NAME,
            [
                'handler_identifier' => InvoicePaymentHandler::class
            ],
            [
                'handler_identifier' => 'Billie\BilliePayment\Components\PaymentMethod\PaymentHandler\PaymentHandler'
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
