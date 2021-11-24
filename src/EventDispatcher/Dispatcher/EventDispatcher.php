<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Dispatcher;

use Closure;
use ReflectionClass;
use InvalidArgumentException;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\Mutable;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\EventDispatcher\Contracts\EventParser;
use Snicco\EventDispatcher\Contracts\ObjectCopier;
use Snicco\EventDispatcher\Contracts\ListenerFactory;
use Snicco\EventDispatcher\Implementations\GenericEvent;
use Snicco\EventDispatcher\Contracts\IsForbiddenToWordPress;
use Snicco\EventDispatcher\Contracts\DispatchesConditionally;
use Snicco\EventDispatcher\Implementations\NativeObjetCopier;
use Snicco\EventDispatcher\Implementations\GenericEventParser;
use Snicco\EventDispatcher\Exceptions\UnremovableListenerException;
use Snicco\EventDispatcher\Implementations\ParameterBasedListenerFactory;

use function Snicco\EventDispatcher\functions\validatedListener;
use function Snicco\EventDispatcher\functions\isWildCardEventListener;
use function Snicco\EventDispatcher\functions\getTypeHintedEventFromClosure;
use function Snicco\EventDispatcher\functions\wildcardPatternMatchesEventName;

/**
 * @api
 */
final class EventDispatcher implements Dispatcher
{
    
    /**
     * Internal interfaces that should not be used as a listener alias.
     *
     * @var string[]
     */
    private $internal_interfaces = [
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
    private $listeners = [];
    
    /**
     * All listeners that are marked as unremovable keyed by event name
     *
     * @var array<string,array>
     */
    private $unremovable = [];
    
    /**
     * The listener factory will be responsible for instantiating a matching listener.
     *
     * @var ListenerFactory
     */
    private $listener_factory;
    
    /**
     * Used the make immutable copies of event objects. By default, the native "clone" function
     * will be used. If your event objects contain public properties that are in and of themselves
     * object you should consider using something like: https://github.com/myclabs/DeepCopy
     *
     * @var ObjectCopier
     */
    private $object_copier;
    
    /**
     * @var EventParser|GenericEventParser
     */
    private $event_parser;
    
    /**
     * Cache of the matching listeners keyed by event name. Used so that we don't have to match
     * again if a wildcard event were to be dispatched a second time.
     * The cache is reset everytime a new event is added.
     *
     * @var array<string,array>
     */
    private $wildcards_listeners_cache = [];
    
    /**
     * Array of the wildcard listeners keyed by the wildcard pattern.
     *
     * @var array<string,array<Closure>>
     */
    private $wildcard_listeners = [];
    
    /**
     * @var array<string,string>
     */
    private $muted_events = [];
    
    /**
     * @var array<string,string[]>
     */
    private $muted_listeners = [];
    
    /**
     * @param  ListenerFactory|null  $listener_factory
     * @param  EventParser|null  $event_parser
     * @param  ObjectCopier|null  $object_copier
     */
    public function __construct(
        ?ListenerFactory $listener_factory = null,
        ?EventParser $event_parser = null,
        ?ObjectCopier $object_copier = null
    ) {
        $this->listener_factory = $listener_factory ?? new ParameterBasedListenerFactory();
        $this->event_parser = $event_parser ?? new GenericEventParser();
        $this->object_copier = $object_copier ?? new NativeObjetCopier();
    }
    
    public function dispatch($event, ...$payload) :Event
    {
        $event = $this->event_parser->transformToEvent(
            $event,
            $payload
        );
        
        if ( ! $this->shouldDispatch($event)) {
            return $event;
        }
        
        foreach ($this->getListenersForEvent($event->getName()) as $listener) {
            $this->callListener(
                $listener,
                $this->getPayloadForCurrentIteration($event),
            );
        }
        
        return $event;
    }
    
    public function listen($event_name, $listener = null, bool $can_be_removed = true) :void
    {
        if ($event_name instanceof Closure) {
            $this->listen(getTypeHintedEventFromClosure($event_name), $event_name);
            return;
        }
        $listener = validatedListener($listener);
        
        if (isWildCardEventListener($event_name)) {
            $this->registerWildcardListener($event_name, $listener);
            return;
        }
        
        $this->listeners[$event_name][$id = $this->getListenerID($listener)] = $listener;
        
        if ( ! $can_be_removed) {
            $this->unremovable[$event_name][$id] = $listener;
        }
    }
    
    public function remove(string $event_name, $listener = null) :void
    {
        if (is_null($listener)) {
            unset($this->listeners[$event_name]);
            return;
        }
        
        if ( ! isset($this->listeners[$event_name])) {
            return;
        }
        
        $id = $this->getListenerID($listener = validatedListener($listener));
        
        if ( ! isset($this->listeners[$event_name][$id])) {
            return;
        }
        
        if (isset($this->unremovable[$event_name][$id])) {
            throw UnremovableListenerException::becauseTheDeveloperTriedToRemove(
                $listener,
                $event_name
            );
        }
        
        unset($this->listeners[$event_name][$id]);
    }
    
    public function mute(string $event_name, $listener = null) :void
    {
        if ($listener === null) {
            $this->muted_events[$event_name] = $event_name;
            return;
        }
        
        $this->muted_listeners[$event_name][$id = $this->getListenerID($listener)] = $id;
    }
    
    public function unmute(string $event_name, $listener = null) :void
    {
        if ($listener === null) {
            unset($this->muted_events[$event_name]);
            return;
        }
        unset($this->muted_listeners[$event_name][$this->getListenerID($listener)]);
    }
    
    private function getListenersForEvent(string $event_name) :array
    {
        $listeners = $this->listeners[$event_name] ?? [];
        
        $listeners = array_merge($listeners, $this->getWildCardListeners($event_name));
        
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
        
        if ($parent && (new ReflectionClass($parent))->isAbstract()) {
            $listeners = array_merge($listeners, $this->getListenersForEvent($parent));
        }
        
        return array_filter($listeners, function ($listener) use ($event_name) {
            return ! isset($this->muted_listeners[$event_name][$this->getListenerID($listener)]);
        });
    }
    
    private function callListener($listener, Event $event) :void
    {
        $this->listener_factory->create($listener, $event->getName())
                               ->call($event);
    }
    
    private function getPayloadForCurrentIteration(Event $payload) :Event
    {
        return $payload instanceof Mutable ? $payload : $this->object_copier->copy($payload);
    }
    
    private function shouldDispatch(Event $event) :bool
    {
        if (isset($this->muted_events[$event->getName()])) {
            return false;
        }
        
        return $event instanceof DispatchesConditionally ? $event->shouldDispatch() : true;
    }
    
    private function registerWildcardListener(string $event_name, $listener)
    {
        $this->wildcard_listeners[$event_name][$this->getListenerID($listener)] =
            function (...$payload) use ($listener) {
                // For wildcard listeners we want to pass the event name as the first argument.
                $event_name = array_pop($payload);
                array_unshift($payload, $event_name);
                
                $listener = $this->listener_factory->create($listener, $event_name);
                $listener->call(new GenericEvent($event_name, $payload));
            };
        
        $this->wildcards_listeners_cache = [];
    }
    
    private function getWildCardListeners(string $event_name) :array
    {
        if (isset($this->wildcards_listeners_cache[$event_name])) {
            return $this->wildcards_listeners_cache[$event_name] ?? [];
        }
        
        $wildcards = [];
        
        foreach ($this->wildcard_listeners as $wildcard_pattern => $listeners) {
            if (wildcardPatternMatchesEventName($wildcard_pattern, $event_name)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }
        
        return $this->wildcards_listeners_cache[$event_name] = $wildcards;
    }
    
    private function getListenerID($listener) :string
    {
        if ($listener instanceof Closure) {
            return spl_object_hash($listener);
        }
        
        if (is_array($listener)) {
            return implode('.', $listener);
        }
        
        throw new InvalidArgumentException(
            "A listener has to be a closure or an class callable passed as an array."
        );
    }
    
}