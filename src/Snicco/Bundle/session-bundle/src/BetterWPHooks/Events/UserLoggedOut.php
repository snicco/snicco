<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\BetterWPHooks\Events;

use Snicco\Component\Core\EventDispatcher\Events\CoreEvent;
use Snicco\Component\BetterWPHooks\EventMapping\MappedAction;

class UserLoggedOut extends CoreEvent implements MappedAction
{
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}