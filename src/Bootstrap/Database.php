<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Bootstrap;

use Billie\BilliePayment\Util\MigrationHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class Database extends AbstractBootstrap
{
    /**
     * @var Connection
     */
    protected $connection;

    public function injectServices(): void
    {
        $this->connection = $this->container->get(Connection::class);
    }

    public function install(): void
    {
    }

    public function update(): void
    {
    }

    /**
     * @throws DBALException
     */
    public function uninstall(bool $keepUserData = false): void
    {
        if ($keepUserData) {
            return;
        }

        $method = MigrationHelper::getExecuteStatementMethod();
        $this->connection->{$method === 'executeStatement' ? $method : 'exec'}('SET FOREIGN_KEY_CHECKS=0;');
        $this->connection->{$method}('DROP TABLE IF EXISTS `billie_payment_config`');
        $this->connection->{$method}('DROP TABLE IF EXISTS `billie_order_data`');
        $this->connection->{$method === 'executeStatement' ? $method : 'exec'}('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function activate(): void
    {
    }

    public function deactivate(): void
    {
    }
}
