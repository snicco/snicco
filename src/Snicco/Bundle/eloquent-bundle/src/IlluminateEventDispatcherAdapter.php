<?php

declare(strict_types=1);

namespace Snicco\EloquentBundle;

use Closure;
use ReflectionException;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as IlluminateEventDispatcher;

final class IlluminateEventDispatcherAdapter implements IlluminateEventDispatcher
{
    
    /**
     * @var Dispatcher
     */
    private $dispatcher;
    
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    /**
     * @param  array|Closure|string  $events
     * @param  null  $listener
     *
     * @throws ReflectionException
     */
    public function listen($events, $listener = null)
    {
        if (is_string($listener) && strpos($listener, '@')) {
            $listener = explode('@', $listener);
        }
        
        if ($events instanceof Closure || is_string($events)) {
            $this->dispatcher->listen($events, $listener);
            return;
        }
        
        foreach ((array) $events as $event) {
            $this->dispatcher->listen($event, $listener);
        }
    }
    
    public function hasListeners($eventName)
    {
        throw new BadMethodCallException(
            sprintf(
                "%s does not support reaching into internal state.",
                get_class($this->dispatcher)
            )
        );
    }
    
    public function subscribe($subscriber)
    {
        throw new BadMethodCallException(
            sprintf(
                '%s currently does not support event subscribers.',
                get_class($this->dispatcher)
            )
        );
    }
    
    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload);
    }
    
    public function dispatch($event, $payload = [], $halt = false)
    {
        $payload = is_array($payload) ? $payload : [$payload];
        
        if (isset($payload[0]) && $payload[0] instanceof Model) {
            $event = new EloquentEvent($payload[0], $event);
        }
        
        return $this->dispatcher->dispatch($event, ...$payload);
    }
    
    public function push($event, $payload = [])
    {
        throw new BadMethodCallException(
            sprintf(
                '%s does not support queued events.',
                get_class($this->dispatcher)
            )
        );
    }
    
    public function flush($event)
    {
        throw new BadMethodCallException(
            sprintf(
                '%s does not support queued events.',
                get_class($this->dispatcher)
            )
        );
    }
    
    public function forget($event)
    {
        $this->dispatcher->remove($event);
    }
    
    public function forgetPushed()
    {
        throw new BadMethodCallException(
            sprintf(
                '%s does not support queued events.',
                get_class($this->dispatcher)
            )
        );
    }
    
}