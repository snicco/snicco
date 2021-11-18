<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Snicco\EventDispatcher\Implementations\ShareWithWordPress;

/**
 * An implementation of this interface is injected into the EventDispatcher and is responsible for
 * deciding what should happen after listeners inside the dispatcher were called.
 * You can customize the behaviour to your liking by injected a different implementation of
 * this interface into the EventDispatcher.
 *
 * @see ShareWithWordPress::share()
 * @api
 */
interface EventSharing
{
    
    /**
     * @param  Event  $event
     */
    public function share(Event $event) :void;
    
}