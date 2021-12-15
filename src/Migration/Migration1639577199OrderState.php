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

class Migration1639577199OrderState extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1639577199;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `billie_order_data`
                ADD `order_state` VARCHAR(20) NOT NULL AFTER `reference_id`
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
