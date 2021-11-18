<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Snicco\EventDispatcher\ImmutableEvent;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\Mutable;
use Snicco\EventDispatcher\Contracts\EventSharing;
use Snicco\EventDispatcher\Contracts\IsForbiddenToWordPress;

final class ShareWithWordPress implements EventSharing
{
    
    public function share(Event $event)
    {
        $event_name = $event->getName();
        
        if ($event instanceof IsForbiddenToWordPress) {
            return;
        }
        
        if ( ! has_filter($event_name)) {
            return $event;
        }
        
        if ($event instanceof Mutable) {
            // Don't return the returned value of apply_filters() since third party devs might return something completely wrong.
            // Since our event is mutable it is passed by reference here. Developers can manipulate the event object directly
            // within our defined constraints.
            apply_filters($event_name, $event);
        }
        else {
            // Make an immutable copy of the event. Developers can interact with this event object the same
            // way as with the original event expect that public properties are read only.
            do_action($event_name, new ImmutableEvent($event));
        }
    }
    
}