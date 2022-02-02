<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\ListenerFactory;

use Snicco\Component\EventDispatcher\Exception\CantCreateListener;

/**
 * @api
 */
interface ListenerFactory
{

    /**
     * @template T
     * @param class-string<T> $listener_class
     * @return  T
     *
     * @throws CantCreateListener
     */
    public function create(string $listener_class, string $event_name): object;

}