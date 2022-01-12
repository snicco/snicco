<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\AdminDashboard;

interface AdminMenu
{
    
    public function add(AdminMenuItem $menu_item);
    
}