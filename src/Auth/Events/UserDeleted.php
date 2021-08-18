<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use Snicco\Events\Event;
use BetterWpHooks\Traits\IsAction;

class UserDeleted extends Event
{
    
    use IsAction;
    
    public int $deleted_user_id;
    
    public function __construct(int $deleted_user_id)
    {
        
        $this->deleted_user_id = $deleted_user_id;
    }
    
}