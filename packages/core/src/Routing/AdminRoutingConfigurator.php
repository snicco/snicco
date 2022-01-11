<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

interface AdminRoutingConfigurator extends RoutingConfigurator
{
    
    public function admin(string $name, string $path, $action = Route::DELEGATE, MenuItem $menu_item = null) :Route;
    
}