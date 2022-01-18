<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment\Util;

use Doctrine\DBAL\Connection;

class MigrationHelper
{
    public static function getExecuteStatementMethod(): string
    {
        return (new \ReflectionClass(Connection::class))
            ->hasMethod('executeStatement') ? 'executeStatement' : 'executeQuery';
    }
}
