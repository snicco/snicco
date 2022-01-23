<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

use Closure;
use ReflectionException;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Snicco\Component\EventDispatcher\Exception\CantRemove;
use Snicco\Component\EventDispatcher\Exception\InvalidListener;

/**
 * @api
 */
interface EventDispatcher extends EventDispatcherInterface
{
    
    /**
     * @param  string|Closure  $event_name  If  the event name is a closure the event will be
     * retrieved from the first typehinted parameter in the closure.
     * @param  Closure|string|array<string,string>  $listener
     *
     * @throws InvalidListener|ReflectionException|InvalidArgumentException
     */
    public function listen($event_name, $listener = null, bool $can_be_removed = true) :void;
    
    /**
     * @param  string  $event_subscriber  a class name that implements {@see EventSubscriber}
     */
    public function subscribe(string $event_subscriber) :void;
    
    /**
     * @note Wildcard Listeners can not be removed. They can be muted instead for a specific
     *       pattern.
     *
     * @param  string  $event_name
     * @param  string|array|null  $listener
     *
     * @throws CantRemove
     * @throws InvalidListener
     */
    public function remove(string $event_name, $listener = null) :void;
    
}