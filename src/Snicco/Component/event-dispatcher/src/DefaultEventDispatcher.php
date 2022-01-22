<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

use Closure;
use ReflectionClass;
use InvalidArgumentException;
use Snicco\Component\EventDispatcher\Exception\CantRemove;
use Snicco\Component\EventDispatcher\Exception\InvalidListener;
use Psr\EventDispatcher\StoppableEventInterface as StoppablePsrEvent;
use Snicco\Component\EventDispatcher\ListenerFactory\ListenerFactory;
use Snicco\Component\EventDispatcher\ListenerFactory\NewableListenerFactory;

use function in_array;
use function is_array;
use function is_string;
use function array_merge;
use function class_exists;
use function method_exists;
use function class_implements;
use function get_parent_class;
use function Snicco\Component\EventDispatcher\functions\getTypeHintedObjectFromClosure;

/**
 * @api
 */
final class DefaultEventDispatcher implements EventDispatcher
{
    
    private ListenerFactory $listener_factory;
    
    /**
     * @var string[]
     */
    private array $internal_interfaces = [
        Event::class,
    ];
    
    /**
     * @var array<string,array<array,Closure>>
     */
    private $listeners = [];
    
    /**
     * All listeners that are marked as unremovable keyed by event name
     *
     * @var array<string,array<array,Closure>>
     */
    private $unremovable = [];
    
    /**
     * @var array<string,array<array,Closure>>
     */
    private array $listener_cache = [];
    
    public function __construct(?ListenerFactory $listener_factory = null)
    {
        $this->listener_factory = $listener_factory ? : new NewableListenerFactory();
    }
    
    public function dispatch(object $event) :object
    {
        $original_event = $event;
        $can_be_stopped = $original_event instanceof StoppablePsrEvent;
        
        $event = $this->transform($original_event);
        
        foreach ($this->getListenersForEvent($event->name()) as $listener) {
            if ($can_be_stopped && $original_event->isPropagationStopped()) {
                break;
            }
            
            $this->callListener(
                $listener,
                $event,
            );
        }
        
        return $original_event;
    }
    
    public function listen($event_name, $listener = null, bool $can_be_removed = true) :void
    {
        if ($event_name instanceof Closure) {
            $this->listen(getTypeHintedObjectFromClosure($event_name), $event_name);
            return;
        }
        
        $this->resetListenerCache($event_name);
        
        $listener = $this->validatedListener($listener);
        
        $this->listeners[$event_name][$id = $this->parseListenerId($listener)] = $listener;
        
        if (false === $can_be_removed) {
            $this->unremovable[$event_name][$id] = $listener;
        }
    }
    
    public function remove(string $event_name, $listener = null) :void
    {
        $this->resetListenerCache($event_name);
        
        if (is_null($listener)) {
            unset($this->listeners[$event_name]);
            return;
        }
        
        if ( ! isset($this->listeners[$event_name])) {
            return;
        }
        
        $id = $this->parseListenerId($listener = $this->validatedListener($listener));
        
        if ( ! isset($this->listeners[$event_name][$id])) {
            return;
        }
        
        if (isset($this->unremovable[$event_name][$id])) {
            throw CantRemove::listenerThatIsMarkedAsUnremovable(
                $listener,
                $event_name
            );
        }
        
        unset($this->listeners[$event_name][$id]);
    }
    
    private function getListenersForEvent(string $event_name) :array
    {
        if (isset($this->listener_cache[$event_name])) {
            $listeners = $this->listener_cache[$event_name];
        }
        else {
            $listeners = $this->listeners[$event_name] ?? [];
            
            if (class_exists($event_name)) {
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
            }
        }
        
        $this->listener_cache[$event_name] = $listeners;
        
        return array_filter($listeners, function ($listener) use ($event_name) {
            return ! isset($this->muted_listeners[$event_name][$this->parseListenerId($listener)]);
        });
    }
    
    private function callListener($listener, Event $event) :void
    {
        $payload = $event->payload();
        $payload = is_array($payload) ? $payload : [$payload];
        
        if ($listener instanceof Closure) {
            $listener(...$payload);
            return;
        }
        
        $instance = $this->listener_factory->create(
            $listener[0],
            $event->name()
        );
        
        $instance->{$listener[1]}(...$payload);
    }
    
    /**
     * @param  array|Closure  $validated_listener
     *
     * @return string
     */
    private function parseListenerId($validated_listener) :string
    {
        if ($validated_listener instanceof Closure) {
            return spl_object_hash($validated_listener);
        }
        
        if (is_array($validated_listener)) {
            return implode('.', $validated_listener);
        }
        
        throw new InvalidArgumentException(
            '$validated_listener has to be a closure or an class callable passed as an array.'
        );
    }
    
    private function resetListenerCache(string $event_name) :void
    {
        unset($this->listener_cache[$event_name]);
    }
    
    private function validatedListener($listener)
    {
        if ($listener instanceof Closure) {
            return $listener;
        }
        
        if (is_string($listener)) {
            if ( ! class_exists($listener)) {
                throw InvalidListener::becauseListenerClassDoesntExist($listener);
            }
            
            $invokable = method_exists($listener, '__invoke');
            
            if ( ! $invokable) {
                throw InvalidListener::becauseListenerCantBeInvoked($listener);
            }
            
            return [$listener, '__invoke'];
        }
        
        if ( ! is_array($listener)) {
            throw new InvalidListener('Listeners must be a string, array or closure.');
        }
        
        if ( ! class_exists($listener[0])) {
            throw InvalidListener::becauseListenerClassDoesntExist($listener[0]);
        }
        
        if ( ! isset($listener[1])) {
            $listener[1] = '__invoke';
        }
        
        if ( ! method_exists($listener[0], $listener[1])) {
            throw InvalidListener::becauseProvidedClassMethodDoesntExist($listener);
        }
        return $listener;
    }
    
    private function transform(object $event) :Event
    {
        if ($event instanceof Event) {
            return $event;
        }
        return GenericEvent::fromObject($event);
    }
    
}