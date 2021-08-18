<?php

declare(strict_types=1);

namespace Snicco\Session\Events;

use Snicco\Events\Event;
use Snicco\Session\Session;

class SessionRegenerated extends Event
{
    
    public Session $session;
    
    public function __construct(Session $session)
    {
        $this->session = $session;
    }
    
}