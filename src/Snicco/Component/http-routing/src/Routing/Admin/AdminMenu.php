<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Admin;

use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int,AdminMenuItem>
 */
interface AdminMenu extends IteratorAggregate
{

    /**
     * @return list<AdminMenuItem>
     */
    public function items(): array;

    /**
     * @return Traversable<int,AdminMenuItem>
     */
    public function getIterator(): Traversable;

}