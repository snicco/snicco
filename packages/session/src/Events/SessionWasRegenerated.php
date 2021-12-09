<?php

declare(strict_types=1);

namespace Snicco\Session\Events;

use Snicco\Session\Session;
use Snicco\Core\EventDispatcher\Events\CoreEvent;

class SessionWasRegenerated extends CoreEvent
{
    
    public Session $session;
    
    public function __construct(Session $session)
    {
        $this->session = $session;
    }
    
}