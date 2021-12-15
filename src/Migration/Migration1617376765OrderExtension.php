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

class Migration1617376765OrderExtension extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1617376765;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `billie_order_data` (
              `id` binary(16) NOT NULL,
              `version_id` BINARY(16) NOT NULL,
              `order_id` binary(16) NOT NULL,
              `order_version_id` binary(16) NOT NULL,
              `reference_id` varchar(255) NOT NULL,
              `duration` int NOT NULL,
              `external_invoice_number` VARCHAR(255) NULL,
              `external_invoice_url` TEXT NULL,
              `external_delivery_note_url` TEXT NULL,
              `successful` tinyint(1) NOT NULL,
              `updated_at` DATETIME NULL,
              PRIMARY KEY (`id`, `version_id`),
              FOREIGN KEY (`order_id`,`order_version_id`) REFERENCES `order` (`id`, `version_id`) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
