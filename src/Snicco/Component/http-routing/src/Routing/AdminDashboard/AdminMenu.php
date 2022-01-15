<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\AdminDashboard;

use IteratorAggregate;

/**
 * @api
 */
interface AdminMenu extends IteratorAggregate
{
    
    /**
     * @return AdminMenuItem[]
     */
    public function items() :array;
    
}