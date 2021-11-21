<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use WP_User;
use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;
use Snicco\EventDispatcher\Contracts\Event;

class Registration implements Event
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public WP_User $user;
    
    public function __construct(WP_User $user)
    {
        $this->user = $user;
    }
    
}