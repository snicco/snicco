<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Closure;
use ReflectionClass;
use ReflectionException;
use InvalidArgumentException;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\Mutable;
use Snicco\EventDispatcher\Contracts\ListenerFactory;
use Snicco\EventDispatcher\Contracts\CustomizablePayload;
use Snicco\EventDispatcher\Contracts\IsForbiddenToWordPress;
use Snicco\EventDispatcher\Contracts\DispatchesConditionally;

use function has_filter;
use function apply_filters;
use function Snicco\EventDispatcher\functions\normalizeListener;
use function Snicco\EventDispatcher\functions\getTypeHintedEventFromClosure;

final class EventDispatcher
{
    
    /**
     * Internal interfaces that should be used as a listener alias.
     *
     * @var string[]
     */
    private array $internal_interfaces = [
        CustomizablePayload::class,
        DispatchesConditionally::class,
        Event::class,
        IsForbiddenToWordPress::class,
        Mutable::class,
    ];
    
    /**
     * All the registered events keyed by event name
     *
     * @var array<string,array>
     */
    private array $listeners = [];
    
    /**
     * All listeners that are marked as unremovable for an event name
     *
     * @var array<string,array>
     */
    private array $unremovable = [];
    
    /**
     * The listener factory will be responsible for creating a matching listener.
     *
     * @var ListenerFactory
     */
    private ListenerFactory $listener_factory;
    
    /**
     * @param  ListenerFactory  $listener_factory
     */
    public function __construct(ListenerFactory $listener_factory)
    {
        $this->listener_factory = $listener_factory;
    }
    
    /**
     * @param  string|Closure  $event_name  If  the event name is a closure the event will be
     * retrieved from the first closure parameter.
     * @param  array|string|Closure  $listener  Array: [class, method].
     * string: A class that either has a handle method or the __invoke method defined.
     * Closure: The closure will be used as is. No additional dependencies will be passed to the
     *     closure besides the event object.
     * @param  bool  $can_be_removed
     *
     * @throws InvalidListenerException|InvalidArgumentException|ReflectionException
     * @api
     */
    public function listen($event_name, $listener = null, bool $can_be_removed = true)
    {
        if ($event_name instanceof Closure) {
            $this->listen(getTypeHintedEventFromClosure($event_name), $event_name);
            return;
        }
        
        $listener = normalizeListener($listener);
        
        if ($listener instanceof Closure) {
            $this->listeners[$event_name][] = $listener;
        }
        else {
            $this->listeners[$event_name][$listener[0]] = $listener;
        }
        
        if ( ! $can_be_removed) {
            $this->unremovable[$event_name][$listener[0]] = $listener[0];
        }
    }
    
    /**
     * @param  string|Event  $event
     * @param  array  $payload
     *
     * @return void|mixed
     * @api
     */
    public function dispatch($event, array $payload = [])
    {
        [$event_name, $event] = $this->getEventAndPayload($event, $payload);
        
        if ( ! $event instanceof Event) {
            $event = new GenericEvent($event);
        }
        
        if ( ! $this->shouldDispatch($event)) {
            return $event;
        }
        
        foreach ($this->getListenersForEvent($event_name) as $listener) {
            $this->callListener(
                $listener,
                $this->getPayloadForCurrentIteration($event)
            );
        }
        
        if ($event instanceof IsForbiddenToWordPress) {
            return $event;
        }
        
        if ( ! has_filter($event_name)) {
            return $event;
        }
        
        if ($event instanceof Mutable) {
            // Don't return the returned value of apply_filters() since third party devs return something completely wrong.
            apply_filters($event_name, $event);
        }
        else {
            do_action($event_name, new ImmutableEvent($event));
        }
        return $event;
    }
    
    /**
     * @note Closure listeners can't be removed.
     *
     * @param  string  $event_name
     * @param  string|null  $listener_class
     *
     * @throws InvalidListenerException|InvalidArgumentException
     * @api
     */
    public function remove(string $event_name, string $listener_class = null)
    {
        if (is_null($listener_class)) {
            unset($this->listeners[$event_name]);
            return;
        }
        
        if (isset($this->listeners[$event_name])
            && isset($this->listeners[$event_name][$listener_class])) {
            if (isset($this->unremovable[$event_name][$listener_class])) {
                throw new UnremovableListenerException(
                    "The listener [$listener_class] is marked as unremovable for the event [$event_name]."
                );
            }
            
            unset($this->listeners[$event_name][$listener_class]);
        }
    }
    
    private function getListenersForEvent(string $event_name) :array
    {
        $listeners = $this->listeners[$event_name] ?? [];
        
        if ( ! class_exists($event_name)) {
            return $listeners;
        }
        
        foreach ((array) class_implements($event_name) as $interface) {
            if (in_array($interface, $this->internal_interfaces, true)) {
                continue;
            }
            $listeners = array_merge($listeners, $this->getListenersForEvent($interface));
        }
        
        $parent = get_parent_class($event_name);
        
        if ( ! $parent || ! (new ReflectionClass($parent))->isAbstract()) {
            return $listeners;
        }
        
        return array_merge($listeners, $this->getListenersForEvent($parent));
    }
    
    private function getEventAndPayload($event_name, array $payload) :array
    {
        if ($event_name instanceof Event) {
            return [get_class($event_name), $event_name];
        }
        
        return [$event_name, $payload];
    }
    
    private function callListener($listener, Event $event)
    {
        return $this->listener_factory->create($listener)->call($event);
    }
    
    private function getPayloadForCurrentIteration(Event $payload) :Event
    {
        return $payload instanceof Mutable ? $payload : clone $payload;
    }
    
    private function shouldDispatch(Event $event) :bool
    {
        return $event instanceof DispatchesConditionally ? $event->shouldDispatch() : true;
    }
    
}