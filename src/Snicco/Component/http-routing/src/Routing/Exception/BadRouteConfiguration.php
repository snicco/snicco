<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Exception;

use LogicException;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Throwable;

final class BadRouteConfiguration extends LogicException
{
    public static function fromPrevious(Throwable $previous): BadRouteConfiguration
    {
        return new self($previous->getMessage(), (int) $previous->getCode(), $previous);
    }

    public static function becauseAdminRouteWasAddedWithHardcodedPrefix(
        string $name,
        string $admin_prefix
    ): BadRouteConfiguration {
        return new self(
            sprintf(
                "You should not add the prefix [%s] to the admin route [%s].\nThis is handled at the framework level.",
                $admin_prefix,
                $name
            )
        );
    }

    public static function becauseDelegatedAttributesHaveNotBeenGrouped(string $route_name): BadRouteConfiguration
    {
        return new self(
            "Cant register route [{$route_name}] because delegated attributes have not been merged into a route group.\nDid you forget to call group() ?"
        );
    }

    public static function becauseFallbackRouteIsAlreadyRegistered(string $name): BadRouteConfiguration
    {
        return new self(sprintf('Route [%s] was registered after a fallback route was defined.', $name));
    }

    public static function becauseWebRouteHasAdminPrefix(string $name): BadRouteConfiguration
    {
        return new self(
            sprintf(
                'You tried to register the route [%s] that goes to the admin dashboard without using the dedicated admin() method on an instance of [%s].',
                $name,
                AdminRoutingConfigurator::class
            )
        );
    }

    public static function becauseAdminRouteHasSegments(string $name): BadRouteConfiguration
    {
        return new self("Admin routes can not define route parameters.\nViolating route [{$name}].");
    }
}
