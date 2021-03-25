<?php declare(strict_types=1);

namespace Billie\BilliePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1616072943PaymentMethodConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1616072943;
    }

    public function update(Connection $connection): void
    {
        $connection->exec("
            CREATE TABLE `billie_payment_config` (
                `payment_method_id` binary(16) NOT NULL,
                `duration` int(11) NOT NULL DEFAULT '14',
                PRIMARY KEY (`payment_method_id`),
                CONSTRAINT `billie_payment_config_ibfk_1` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeQuery("DROP TABLE IF EXISTS `billie_payment_config`");
    }
}
