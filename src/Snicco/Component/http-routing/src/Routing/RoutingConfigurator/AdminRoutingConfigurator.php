<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RoutingConfigurator;

use Closure;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\Route;

/**
 * @api
 */
interface AdminRoutingConfigurator extends RoutingConfigurator, AdminMenu
{

    /**
     * A menu item will be added in the following scenario:
     * $action !== Route::DELEGATE && $menu_attributes !== null
     * Passing an array of attributes has no effect when the Route delegates the response handling.
     *
     * @param Route|string|null $parent
     *
     * @throws BadRouteConfiguration
     */
    public function page(
        string $name,
        string $path,
        $action = Route::DELEGATE,
        ?array $menu_attributes = [],
        $parent = null
    ): Route;

    /**
     * @throws BadRouteConfiguration
     */
    public function subPages(Route $parent_route, Closure $routes): void;

}