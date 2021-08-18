<?php

declare(strict_types=1);

namespace Snicco\Database\Illuminate;

use RuntimeException;
use Snicco\Support\Str;
use Illuminate\Contracts\Events\Dispatcher;
use BetterWpHooks\Dispatchers\WordpressDispatcher;

/**
 * @todo Move this to dedicated laravel-bridge package
 */
class IlluminateDispatcherAdapter implements Dispatcher
{
    
    private WordpressDispatcher $dispatcher;
    
    public function __construct(WordpressDispatcher $dispatcher)
    {
        
        $this->dispatcher = $dispatcher;
    }
    
    public function listen($events, $listener = null)
    {
        /** @todo Explore compatibility for wildcard events. */
        if ( ! is_string($events) || Str::contains('*', $events)) {
            throw new RuntimeException(
                'BetterWP does only support eloquent events registered as string at the moment.'
            );
        }
        
        $this->dispatcher->listen($events, $listener);
        
    }
    
    public function hasListeners($eventName)
    {
        return $this->dispatcher->hasListeners($eventName);
    }
    
    public function subscribe($subscriber)
    {
        
        throw new RuntimeException('BetterWP does not support event subscribing at the moment.');
    }
    
    /**
     * NOTE: Its currently not possible with BetterWpHooks or WordPress in general,
     * to run a filter with some stop condition.
     * Because of this we will have to call all listeners for now.
     */
    public function until($event, $payload = [])
    {
        return $this->dispatcher->dispatch($event, $payload);
    }
    
    public function dispatch($event, $payload = [], $halt = false)
    {
        return $this->dispatcher->dispatch($event, $payload);
    }
    
    public function push($event, $payload = [])
    {
        
        throw new RuntimeException('BetterWP does not support event subscribing at the moment.');
        
    }
    
    public function flush($event)
    {
        
        throw new RuntimeException('BetterWP does not support event subscribing at the moment.');
    }
    
    public function forget($event)
    {
        $this->dispatcher->forget($event);
    }
    
    public function forgetPushed()
    {
        
        throw new RuntimeException('BetterWP does not support event subscribing at the moment.');
    }
    
}