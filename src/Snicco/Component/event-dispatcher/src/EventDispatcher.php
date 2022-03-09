<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

use Closure;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;
use Snicco\Component\EventDispatcher\Exception\CantRemoveListener;
use Snicco\Component\EventDispatcher\Exception\InvalidListener;

interface EventDispatcher extends EventDispatcherInterface
{

    /**
     * @template T of object
     *
     * @param string|Closure(T) $event_name If the event name is a closure the event will be
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
     * @param null|Closure|class-string|array{0:class-string, 1:string} $listener
     *
     * @throws CantRemoveListener
     * @throws InvalidListener
     */
    public function remove(string $event_name, $listener = null): void;

    /**
     * @template T of object
     *
     * @param T $event
     *
     * @return T
     */
    public function dispatch(object $event): object;

}