<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent;

use mysqli;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Throwable;
use wpdb;

use const DB_CHARSET;
use const DB_COLLATE;
use const DB_HOST;
use const DB_NAME;
use const DB_PASSWORD;
use const DB_USER;

/**
 * @psalm-internal Snicco\Component\Eloquent
 *
 * @interal
 */
class WPDatabaseSettingsAPI extends BetterWPAPI
{

    public function dbHost(): string
    {
        return DB_HOST;
    }

    public function dbUser(): string
    {
        return DB_USER;
    }

    public function dbName(): string
    {
        return DB_NAME;
    }

    public function dbPassword(): string
    {
        return DB_PASSWORD;
    }

    public function dbCharset(): string
    {
        return DB_CHARSET;
    }

    public function dbCollate(): string
    {
        return DB_COLLATE;
    }

    public function tablePrefix(): string
    {
        return $this->wpdb()->prefix;
    }

    public function wpdb(): wpdb
    {
        return $GLOBALS['wpdb'];
    }

    public function mysqli(): mysqli
    {
        try {
            // This is a protected property in wpdb, but it has __get() access.
            /** @var mysqli $mysqli */
            $mysqli = $this->wpdb()->dbh;
            return $mysqli;
        } catch (Throwable $e) {
            // @codeCoverageIgnoreStart

            // This will work for sure if WordPress where ever
            // to delete magic method accessors, which tbh will probably never happen.
            return (function () {
                return $this->dbh;
            })->call($wpdb);
            // @codeCoverageIgnoreEnd
        }
    }

}