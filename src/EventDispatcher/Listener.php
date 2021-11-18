<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Closure;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\CustomizablePayload;

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
        $payload = $event instanceof CustomizablePayload ? $event->payload() : [$event];
        
        return call_user_func_array(
            $this->listener,
            array_merge($payload, [$event->getName()])
        );
    }
    
}