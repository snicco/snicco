<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\BetterWPHooks\Events;

use Snicco\Core\EventDispatcher\Events\CoreEvent;
use Snicco\EventDispatcher\Contracts\MappedAction;

class UserLoggedOut extends CoreEvent implements MappedAction
{
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}