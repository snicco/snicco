<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\AdminDashboard;

/**
 * @interal
 */
interface AdminMenu
{
    
    public function add(AdminMenuItem $menu_item) :void;
    
}