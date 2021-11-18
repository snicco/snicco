<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Snicco\EventDispatcher\ImmutableEvent;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\Mutable;
use Snicco\EventDispatcher\Contracts\EventSharing;
use Snicco\EventDispatcher\Contracts\IsForbiddenToWordPress;

/**
 * The behaviour of this class is covered by semantic versioning.
 *
 * @api
 */
final class ShareWithWordPress implements EventSharing
{
    
    public function share(Event $event) :void
    {
        $event_name = $event->getName();
        
        if ($event instanceof IsForbiddenToWordPress) {
            return;
        }
        
        if ( ! has_filter($event_name)) {
            return;
        }
        
        if ($event instanceof Mutable) {
            // Since our event is mutable it is passed by reference here. Developers can manipulate the event object directly
            // within our defined constraints.
            // It's not necessary to return anything from this method. We are "filtering" the event object
            // and not a primitive value.
            apply_filters($event_name, $event);
        }
        else {
            // Make an immutable copy of the event. Developers can interact with this event object the same
            // way as with the original event, expect that public properties are read only.
            do_action($event_name, new ImmutableEvent($event));
        }
    }
    
}