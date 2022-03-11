<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RoutingConfigurator;

use Closure;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\Route;

interface AdminRoutingConfigurator extends RoutingConfigurator, AdminMenu
{
    /**
     * A menu item will be added in the following scenario: $action !==
     * Route::DELEGATE && $menu_attributes !== null Passing an array of
     * attributes has no effect when the Route delegates the response handling.
     *
     * @param array{0:class-string, 1:string}|class-string $action
     * @param array{
     * menu_title?: string,
     * page_title?: string,
     * icon?: string,
     * capability?: string,
     * position?: int
     * } $menu_attributes
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
     * @param Closure(AdminRoutingConfigurator):void $routes
     *
     * @throws BadRouteConfiguration
     */
    public function subPages(Route $parent_route, Closure $routes): void;
}
