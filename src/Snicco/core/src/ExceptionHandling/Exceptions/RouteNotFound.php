<?php

declare(strict_types=1);

namespace Snicco\Core\ExceptionHandling\Exceptions;

use InvalidArgumentException;

final class RouteNotFound extends InvalidArgumentException
{
    
    public static function name(string $route_name) :RouteNotFound
    {
        return new self("There is no named route with the name: [$route_name] registered.");
    }
    
    public static function accessByBadName(string $used_name, string $actual_name) :RouteNotFound
    {
        return new self(
            "Route accessed with bad name.\nRoute with real name [$actual_name] is stored with name [$used_name]."
        );
    }
    
}