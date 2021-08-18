<?php

declare(strict_types=1);

namespace Snicco\Events;

use BetterWpHooks\Traits\IsAction;

class IncomingAdminRequest extends IncomingRequest
{
    
    use IsAction;
}