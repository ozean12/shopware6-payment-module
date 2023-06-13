<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Migration;

use Billie\BilliePayment\Util\MigrationHelper;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1686589439BillieInvoiceUuid extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1686589439;
    }

    public function update(Connection $connection): void
    {
        $methodName = MigrationHelper::getExecuteStatementMethod();

        $connection->{$methodName}('
            ALTER TABLE `billie_order_data`
                ADD `invoice_uuid` VARCHAR(255) NULL AFTER `reference_id`
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
