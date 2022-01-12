<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\RoutingConfigurator;

use Snicco\Core\Routing\Route\Route;
use Snicco\Core\Routing\AdminDashboard\AdminMenuItem;

/**
 * @api
 */
interface AdminRoutingConfigurator extends RoutingConfigurator
{
    
    public function admin(string $name, string $path, $action = Route::DELEGATE, AdminMenuItem $menu_item = null) :Route;
    
}