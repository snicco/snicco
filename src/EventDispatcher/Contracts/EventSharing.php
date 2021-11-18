<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

/**
 * @api
 */
interface EventSharing
{
    
    public function share(Event $event);
    
}