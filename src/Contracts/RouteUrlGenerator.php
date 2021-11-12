<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

interface RouteUrlGenerator
{
    
    /**
     * @param  string  $name
     * @param  array  $arguments
     *
     * @return string
     * @throws ConfigurationException
     */
    public function to(string $name, array $arguments) :string;
    
}