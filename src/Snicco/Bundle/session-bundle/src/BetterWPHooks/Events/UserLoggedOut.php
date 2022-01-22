<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\BetterWPHooks\Events;

use Snicco\Component\BetterWPHooks\MappedAction;
use Snicco\Component\Core\EventDispatcher\Events\CoreEvent;

class UserLoggedOut extends CoreEvent implements MappedAction
{
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}