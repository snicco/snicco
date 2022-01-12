<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Exception;

use Throwable;
use LogicException;
use Snicco\Core\Routing\RoutingConfigurator\AdminRoutingConfigurator;

/**
 * @api
 */
final class BadRoute extends LogicException
{
    
    public static function fromPrevious(Throwable $previous) :BadRoute
    {
        return new self($previous->getMessage(), $previous->getCode(), $previous);
    }
    
    public static function becauseAdminRouteWasAddedWithHardcodedPrefix(string $name, $admin_prefix) :BadRoute
    {
        return new self(
            sprintf(
                "You should not add the prefix [%s] to the admin route [%s].\nThis is handled at the framework level.",
                $admin_prefix,
                $name
            )
        );
    }
    
    public static function becauseDelegatedAttributesHaveNotBeenGrouped(string $route_name) :BadRoute
    {
        return new self(
            "Cant register route [$route_name] because delegated attributes have not been merged into a route group.\nDid you forget to call group() ?"
        );
    }
    
    public static function becauseFallbackRouteIsAlreadyRegistered(string $name) :BadRoute
    {
        return new self("Route [$name] was registered after a fallback route was defined.");
    }
    
    public static function becauseWebRouteHasAdminPrefix(string $name) :BadRoute
    {
        return new self(
            sprintf(
                'You tried to register the route [%s] that goes to the admin dashboard without using the dedicated admin() method on an instance of [%s].',
                $name,
                AdminRoutingConfigurator::class
            )
        );
    }
    
}