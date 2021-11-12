<?php

declare(strict_types=1);

namespace Snicco\Database\Concerns;

use wpdb;

/**
 * Trait DelegatesToWpdb
 *
 * @property wpdb $wpdb;
 */
trait DelegatesToWpdb
{
    
    public function __get($name)
    {
        return $this->wpdb->{$name};
    }
    
    public function __set($name, $value)
    {
        $this->wpdb->{$name} = $value;
    }
    
    public function __isset($name)
    {
        return isset($this->wpdb->{$name});
    }
    
    public function __unset($name)
    {
        unset($this->wpdb->{$name});
    }
    
    public function __call($method, $arguments)
    {
        return $this->wpdb->{$method}(...$arguments);
    }
    
}