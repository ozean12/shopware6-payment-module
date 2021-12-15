<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Migration;

use Billie\BilliePayment\Bootstrap\PaymentMethods;
use Billie\BilliePayment\Components\PaymentMethod\Model\Definition\PaymentMethodConfigDefinition;
use Billie\BilliePayment\Components\PaymentMethod\Model\Extension\PaymentMethodExtension;
use Billie\BilliePayment\Util\MigrationHelper;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1616072943PaymentMethodConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1616072943;
    }

    public function update(Connection $connection): void
    {
        $methodName = MigrationHelper::getExecuteStatementMethod();

        $connection->{$methodName}("
            CREATE TABLE `billie_payment_config` (
                `payment_method_id` binary(16) NOT NULL,
                `duration` int(11) NOT NULL DEFAULT '14',
                PRIMARY KEY (`payment_method_id`),
                CONSTRAINT `billie_payment_config_ibfk_1` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // this is a little bit crazy:
        // The migrations will be executed AFTER the plugin method call `install`/`update`.
        // so we must insert the "duration" values after we added the tables.
        // TODO: we should think about a better solution to insert these values
        foreach (PaymentMethods::PAYMENT_METHODS as $method) {
            $connection->{$methodName}('
                REPLACE INTO ' . PaymentMethodConfigDefinition::ENTITY_NAME . '
                    SELECT
                        payment_method.id,
                        ?
                    FROM ' . PaymentMethodDefinition::ENTITY_NAME . '
                    WHERE payment_method.handler_identifier = ?',
                [
                    $method[PaymentMethodExtension::EXTENSION_NAME]['duration'],
                    $method['handlerIdentifier'],
                ]
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
