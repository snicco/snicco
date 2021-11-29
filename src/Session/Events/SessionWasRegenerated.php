<?php

declare(strict_types=1);

namespace Snicco\Session\Events;

use Snicco\Session\Session;
use Snicco\Core\Events\EventObjects\CoreEvent;

class SessionWasRegenerated extends CoreEvent
{
    
    public Session $session;
    
    public function __construct(Session $session)
    {
        $this->session = $session;
    }
    
}