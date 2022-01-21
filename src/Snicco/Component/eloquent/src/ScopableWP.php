<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent;

use wpdb;
use mysqli;
use Throwable;

use const DB_HOST;
use const DB_USER;
use const DB_NAME;
use const DB_CHARSET;
use const DB_COLLATE;
use const DB_PASSWORD;

/**
 * @interal
 */
final class ScopableWP extends \Snicco\Component\ScopableWP\ScopableWP
{
    
    public function dbHost() :string
    {
        return DB_HOST;
    }
    
    public function dbUser() :string
    {
        return DB_USER;
    }
    
    public function dbName() :string
    {
        return DB_NAME;
    }
    
    public function dbPassword() :string
    {
        return DB_PASSWORD;
    }
    
    public function dbCharset() :string
    {
        return DB_CHARSET;
    }
    
    public function dbCollate() :string
    {
        return DB_COLLATE;
    }
    
    public function tablePrefix() :string
    {
        return $this->wpdb()->prefix;
    }
    
    public function mysqli() :mysqli
    {
        try {
            // This is a protected property in wpdb, but it has __get() access.
            /** @var mysqli $mysqli */
            $mysqli = $this->wpdb()->dbh;
            return $mysqli;
        } catch (Throwable $e) {
            // This will work for sure if WordPress where ever
            // to delete magic method accessors, which tbh will probably never happen.
            return (function () {
                return $this->dbh;
            })->call($wpdb);
        }
    }
    
    public function wpdb() :wpdb
    {
        global $wpdb;
        return $wpdb;
    }
    
}