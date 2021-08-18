<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Support\WP;
use BetterWpHooks\Traits\IsAction;
use BetterWpHooks\Traits\DispatchesConditionally;

class BeforeAdminFooter extends Event
{
    
    use IsAction;
    use DispatchesConditionally;
    
    public function shouldDispatch() :bool
    {
        return WP::isAdmin() && ! WP::isAdminAjax();
    }
    
}