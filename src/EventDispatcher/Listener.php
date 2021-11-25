<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Closure;
use Snicco\EventDispatcher\Contracts\Event;

/**
 * @internal
 */
final class Listener
{
    
    /**
     * @var callable
     */
    private $listener;
    
    public function __construct(Closure $listener)
    {
        $this->listener = $listener;
    }
    
    public function call(Event $event)
    {
        $payload = $event->getPayload();
        
        $__payload = is_array($payload) ? $payload : [$payload];
        
        if ( ! in_array($event->getName(), $__payload, true)) {
            $__payload[] = $event->getName();
        }
        
        return call_user_func_array(
            $this->listener,
            $__payload
        );
    }
    
}