<?php declare(strict_types=1);


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
