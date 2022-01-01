<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

interface RouteUrlGeneratorInterface
{
    
    /**
     * @param  string  $name
     * @param  array  $arguments
     *
     * @return string
     * @throws RouteNotFound
     */
    public function to(string $name, array $arguments) :string;
    
}