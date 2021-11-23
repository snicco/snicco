<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Dispatcher;

use Snicco\EventDispatcher\ImmutableEvent;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\Mutable;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\EventDispatcher\Contracts\IsForbiddenToWordPress;

final class WordPressDispatcher implements Dispatcher
{
    
    private Dispatcher $dispatcher;
    
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    /**
     * @inheritdoc
     */
    public function listen($event_name, $listener = null, bool $can_be_removed = true)
    {
        $this->dispatcher->listen($event_name, $listener, $can_be_removed);
    }
    
    /**
     * @inheritdoc
     */
    public function dispatch($event, ...$payload) :Event
    {
        $event = $this->dispatcher->dispatch($event, ...$payload);
        
        $event_name = $event->getName();
        
        if ($event instanceof IsForbiddenToWordPress) {
            return $event;
        }
        
        if ( ! has_filter($event_name)) {
            return $event;
        }
        
        if ($event instanceof Mutable) {
            // Since our event is mutable it is passed by reference here. Developers can manipulate the event object directly
            // within our defined constraints.
            // It's not necessary to return anything from this method. We are "filtering" the event object
            // and not a primitive value.
            // Do not return the result of apply_filters since developers might return arbitrary values.
            apply_filters($event_name, $event);
        }
        else {
            // Make an immutable copy of the event. Developers can interact with this event object the same
            // way as with the original event, expect that public properties are read only.
            do_action($event_name, new ImmutableEvent($event));
        }
        
        return $event;
    }
    
    /**
     * @inheritdoc
     */
    public function remove(string $event_name, string $listener_class = null)
    {
        $this->dispatcher->listen($event_name, $listener_class);
    }
    
}