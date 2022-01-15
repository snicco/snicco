<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Routing\Exception;

use InvalidArgumentException;
use Snicco\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;

/**
 * @api
 */
final class InvalidRouteClosureReturned extends InvalidArgumentException
{
    
    public static function adminRoutesAreUsingWebRouting(string $filepath) :InvalidRouteClosureReturned
    {
        return new self(
            sprintf(
                "The returned closure from the route file\n[%s]\nwill receive an instance of [%s] but required [%s].",
                $filepath,
                AdminRoutingConfigurator::class,
                WebRoutingConfigurator::class
            )
        );
    }
    
    public static function webRoutesAreUsingAdminRouting(string $filepath) :InvalidRouteClosureReturned
    {
        return new self(
            sprintf(
                "The returned closure from the route file\n[%s]\nwill receive an instance of [%s] but required [%s].",
                $filepath,
                WebRoutingConfigurator::class,
                AdminRoutingConfigurator::class
            )
        );
    }
    
    public static function becauseTheFirstParameterIsNotTypeHinted(string $filepath) :InvalidRouteClosureReturned
    {
        return new self(
            sprintf(
                'The closure that was returned from the route file [%s] needs to have an instance of [%s] type-hinted as its first parameter.',
                $filepath,
                RoutingConfigurator::class
            )
        );
    }
    
    public static function becauseTheRouteClosureAcceptsNoArguments(string $path) :InvalidRouteClosureReturned
    {
        return new self(
            sprintf(
                'The closure that was returned from the route file [%s] needs to have an instance of [%s] type-hinted as its first parameter.',
                $path,
                RoutingConfigurator::class
            )
        );
    }
    
    public static function becauseTheRouteClosureAcceptsMoreThanOneArguments(string $path, int $count) :InvalidRouteClosureReturned
    {
        return new self(
            sprintf(
                "The returned closure from the route file\n[%s]\nwill only receive an instance of [%s] but required [%s] parameters.",
                $path,
                RoutingConfigurator::class,
                $count
            )
        );
    }
    
}