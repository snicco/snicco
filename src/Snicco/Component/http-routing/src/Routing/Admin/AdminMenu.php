<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Admin;

interface AdminMenu
{
    /**
     * @return list<AdminMenuItem>
     */
    public function items(): array;
}
