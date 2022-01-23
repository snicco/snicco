<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Exception;

use RuntimeException;

final class RouteNotFound extends RuntimeException
{
    
    public static function name(string $name)
    {
        return new self("There is no route with name [$name].");
    }
    
}