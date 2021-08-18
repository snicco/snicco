<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use WP_User;
use Snicco\Events\Event;
use BetterWpHooks\Traits\IsAction;

class Registration extends Event
{
    
    use IsAction;
    
    public WP_User $user;
    
    public function __construct(WP_User $user)
    {
        $this->user = $user;
    }
    
}