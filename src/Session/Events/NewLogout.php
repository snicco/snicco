<?php

declare(strict_types=1);

namespace Snicco\Session\Events;

use Snicco\Events\Event;
use BetterWpHooks\Traits\IsAction;

class NewLogout extends Event
{
    
    use IsAction;
}