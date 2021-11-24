<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Closure;
use ReflectionException;
use InvalidArgumentException;
use Snicco\EventDispatcher\Exceptions\InvalidEventException;
use Snicco\EventDispatcher\Implementations\GenericEventParser;
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
     * Indicate if the event can be removed. Closures can never be removed.
     *
     * @throws InvalidListenerException|ReflectionException|InvalidArgumentException
     * @api
     */
    public function listen($event_name, $listener = null, bool $can_be_removed = true) :void;
    
    /**
     * @param  string|Event|object  $event  {@see GenericEventParser}
     * @param  array  $payload
     *
     * @return Event
     * @throws InvalidEventException
     * @api
     */
    public function dispatch($event, ...$payload) :Event;
    
    /**
     * @note
     * Wildcard Listeners can not be removed. They can be muted instead for a specific pattern.
     *
     * @param  string  $event_name
     * @param  string|array|null  $listener
     *
     * @throws UnremovableListenerException
     * @throws InvalidListenerException
     * @api
     */
    public function remove(string $event_name, $listener = null) :void;
    
    /**
     * @param  string  $event_name
     * @param  null|Closure|array|string  $listener
     *
     * @throws InvalidListenerException
     * @api
     */
    public function mute(string $event_name, $listener = null) :void;
    
    /**
     * @param  string  $event_name
     * @param  null|Closure|array|string  $listener
     *
     * @throws InvalidListenerException
     * @api
     */
    public function unmute(string $event_name, $listener = null) :void;
    
}