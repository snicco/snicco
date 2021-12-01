<?php

declare(strict_types=1);

namespace Snicco\Session\Events;

use Snicco\EventDispatcher\Events\CoreEvent;
use Snicco\EventDispatcher\Contracts\MappedAction;

class NewLogout extends CoreEvent implements MappedAction
{
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}