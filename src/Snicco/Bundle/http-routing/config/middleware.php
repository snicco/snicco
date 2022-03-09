<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Middleware\ErrorsToExceptions;
use Snicco\Bundle\HttpRouting\Middleware\SetUserId;
use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;

return [
    /*
     * Middleware aliases allow you to reference middleware classes by their alias in route definitions.
     * This can help you to maintain your route files clean and organized. It's however not a requirement
     * to use middleware aliases.
     * You can always reference middleware by its full class-name, so feel free to remove this option.
     */
    MiddlewareOption::ALIASES => [
        //        'foo' => '\MyPlugin\Middleware\FooMiddleware'
    ],

    /*
     * This option defines your custom middleware groups.
     * The group name is the array key and each group consists of either middleware aliases,class-names or other middleware groups.
     */
    MiddlewareOption::GROUPS => [
        RoutingConfigurator::FRONTEND_MIDDLEWARE => [],
        RoutingConfigurator::ADMIN_MIDDLEWARE => [],
        RoutingConfigurator::API_MIDDLEWARE => [],
        RoutingConfigurator::GLOBAL_MIDDLEWARE => [
            SetUserId::class,
        ],
        //        'custom_group1' => [
        //            'foo',
        //            'custom_group2'
        //        ],
        //        'custom_group2' => [
        //            'bar'
        //        ]
    ],

    /*
     * This option allows you to define an order execution for your middleware
     * independently of the order that was used when defining the routes.
     * Middleware that comes first in this array is executed first. Middleware that is not present in this option
     * will always keep its relative priority.
     *
     * Feel free to remove this option if you don't use it in your application.
     */
    MiddlewareOption::PRIORITY_LIST => [
        //        '\MyPlugin\Middleware\FooMiddleware',
        //        '\MyPlugin\Middleware\BarMiddleware',
        //        '\MyPlugin\Middleware\BazMiddleware',
    ],

    /*
     * By default, if no route matches the current request, no middleware is run.
     * This option lets you configure this behaviour.
     * For example: If you put the "admin" group into this array, all middleware from the admin group will
     * be run for every request to the WordPress admin area including request where none of your admin routes matched.
     *
     * Allowed values: "frontend", "api", "global", "admin"
     */
    MiddlewareOption::ALWAYS_RUN => [
        //        'frontend',
        //        'api',
        //        'global',
        //        'admin'
    ],

    /*
     * This option gives you maximum customizable on how the framework performs its request-response-cycle.
     * You should leave this value as is unless you really know what you are doing.
     *
     * This is different from the "global" middleware group.
     * Global middleware is attached to all routes after a match was found.
     * The RoutingMiddleware allows the framework to match a request to a route in the first place.
     */
    MiddlewareOption::KERNEL_MIDDLEWARE => [
        ErrorsToExceptions::class,
        RoutingMiddleware::class,
        RouteRunner::class,
    ],
];
