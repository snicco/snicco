<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\RoutingConfigurator;

use Closure;
use Snicco\Core\Routing\Route\Route;
use Snicco\Core\Routing\AdminDashboard\AdminMenuItems;

/**
 * @api
 */
interface AdminRoutingConfigurator extends RoutingConfigurator, AdminMenuItems
{
    
    /**
     * A menu item will be added in the following scenario:
     * $action !== Route::DELEGATE && $menu_attributes !== null
     * Passing an array of attributes has no effect when the Route delegates the response handling.
     *
     * @param  Route|string|null  $parent
     */
    public function page(string $name, string $path, $action = Route::DELEGATE, ?array $menu_attributes = [], $parent = null) :Route;
    
    public function subPages(Route $parent_route, Closure $routes) :void;
    
}