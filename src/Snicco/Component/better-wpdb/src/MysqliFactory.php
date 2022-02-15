<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB;

use mysqli;
use ReflectionException;
use ReflectionProperty;

final class MysqliFactory
{

    /**
     * @throws ReflectionException
     */
    public static function fromWpdbConnection(): mysqli
    {
        $wpdb = $GLOBALS['wpdb'];

        $dbh = new ReflectionProperty($wpdb, 'dbh');
        $dbh->setAccessible(true);

        /** @var mysqli $mysqli */
        $mysqli = $wpdb->dbh;

        $dbh->setAccessible(false);

        return $mysqli;
    }
}