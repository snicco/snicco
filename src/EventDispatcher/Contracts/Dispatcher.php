<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use ReflectionException;
use InvalidArgumentException;
use Snicco\EventDispatcher\Exceptions\InvalidListenerException;
use Snicco\EventDispatcher\Exceptions\UnremovableListenerException;

/**
 * @api
 */
interface Dispatcher
{
    
    /**
     * @param  string|Closure  $event_name  If  the event name is a closure the event will be
     * retrieved from the first closure parameter.
     * @param  array|string|Closure  $listener
     * @param  bool  $can_be_removed
     *
     * @throws InvalidListenerException|ReflectionException|InvalidArgumentException
     */
    public function listen($event_name, $listener = null, bool $can_be_removed = true);
    
    /**
     * @param  string|Event  $event
     * @param  array  $payload  The payload SHALL NOT be used if $event is an Event object. In this
     *     case the event object itself MUST BE to payload.
     *
     * @return Event
     */
    public function dispatch($event, ...$payload) :Event;
    
    /**
     * @param  string  $event_name
     * @param  string|null  $listener_class
     *
     * @throws UnremovableListenerException
     */
    public function remove(string $event_name, string $listener_class = null);
    
}