<?php

declare(strict_types=1);

namespace Snicco\Database\Contracts;

use Snicco\ExceptionHandling\Exceptions\ConfigurationException;
use Illuminate\Database\ConnectionResolverInterface as IlluminateConnectionResolverInterface;

interface ConnectionResolverInterface extends IlluminateConnectionResolverInterface
{
    
    /**
     * Get a database connection instance.
     *
     * @param  string|null  $name
     *
     * @return WPConnectionInterface
     * @throws ConfigurationException
     */
    public function connection($name = null) :WPConnectionInterface;
    
}