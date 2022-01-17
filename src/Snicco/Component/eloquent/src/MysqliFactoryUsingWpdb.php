<?php

declare(strict_types=1);

namespace Snicco\Database;

use wpdb;
use Closure;
use Throwable;
use Snicco\Database\Contracts\MysqliConnectionFactory;

/**
 * @internal
 * @property $dbh
 */
final class MysqliFactoryUsingWpdb implements MysqliConnectionFactory
{
    
    public function create() :MysqliConnection
    {
        global $wpdb;
        $mysqli = $this->extractActiveMysqliInstance($wpdb);
        
        $reconnect = new Reconnect($this->getReconnect($wpdb));
        
        return new MysqliConnection(
            new MysqliDriver($mysqli, $reconnect),
            $wpdb->prefix,
        );
    }
    
    private function extractActiveMysqliInstance(wpdb $wpdb)
    {
        try {
            // This is a protected property in wpdb, but it has __get() access.
            return $wpdb->dbh;
        } catch (Throwable $e) {
            // This will work for sure if WordPress where ever
            // to delete magic method accessors, which tbh will probably never happen.
            return (function () {
                return $this->dbh;
            })->call($wpdb);
        }
    }
    
    private function getReconnect(wpdb $wpdb) :Closure
    {
        return function () use ($wpdb) {
            $success = $wpdb->check_connection(false);
            if ( ! $success) {
                return false;
            }
            return $this->extractActiveMysqliInstance($wpdb);
        };
    }
    
}