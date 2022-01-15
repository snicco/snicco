<?php

declare(strict_types=1);

namespace Snicco\Auth\Events;

use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;
use Snicco\EventDispatcher\Contracts\Event;

class UserDeleted implements Event
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public int $deleted_user_id;
    
    public function __construct(int $deleted_user_id)
    {
        $this->deleted_user_id = $deleted_user_id;
    }
    
}