<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\AdminDashboard;

use IteratorAggregate;

interface AdminMenuItems extends IteratorAggregate
{
    
    /**
     * @return AdminMenuItem[]
     */
    public function all() :array;
    
}