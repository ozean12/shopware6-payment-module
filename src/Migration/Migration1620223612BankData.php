<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1620223612BankData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1620223612;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `billie_order_data`
                ADD `bank_iban` VARCHAR(255) NOT NULL AFTER `external_delivery_note_url`,
                ADD `bank_bic` VARCHAR(255) NOT NULL AFTER `bank_iban`,
                ADD `bank_name` VARCHAR(255) NOT NULL AFTER `bank_bic`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
