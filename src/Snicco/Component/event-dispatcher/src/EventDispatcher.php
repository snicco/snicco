<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

use Closure;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;
use Snicco\Component\EventDispatcher\Exception\CantRemove;
use Snicco\Component\EventDispatcher\Exception\InvalidListener;

/**
 * @api
 */
interface EventDispatcher extends EventDispatcherInterface
{

    /**
     * @param string|Closure $event_name If the event name is a closure the event will be
     * retrieved from the first typehinted parameter in the closure.
     * @param null|Closure|class-string|array{0:class-string, 1:string} $listener
     *
     * @throws InvalidListener|ReflectionException|InvalidArgumentException
     */
    public function listen($event_name, $listener = null): void;

    /**
     * @param class-string<EventSubscriber> $event_subscriber a class name that implements {@see EventSubscriber}
     */
    public function subscribe(string $event_subscriber): void;

    /**
     *
     * @param string $event_name
     * @param null|Closure|class-string|array{0:class-string, 1:string} $listener
     *
     * @throws CantRemove
     * @throws InvalidListener
     */
    public function remove(string $event_name, $listener = null): void;

}