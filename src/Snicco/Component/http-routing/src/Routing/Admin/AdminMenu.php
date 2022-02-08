<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Admin;

use IteratorAggregate;
use Traversable;

interface AdminMenu extends IteratorAggregate
{

    /**
     * @return AdminMenuItem[]
     */
    public function items(): array;

    /**
     * @return Traversable<AdminMenuItem>
     */
    public function getIterator(): Traversable;

}