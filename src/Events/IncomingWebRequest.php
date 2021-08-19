<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Support\Str;
use BetterWpHooks\Traits\IsAction;
use BetterWpHooks\Traits\DispatchesConditionally;

class IncomingWebRequest extends IncomingRequest
{
    
    use IsAction;
    use DispatchesConditionally;
    
    public function shouldDispatch() :bool
    {
        return $this->request->isWpFrontEnd()
               && ! Str::contains(
                $this->request->path(),
                rest_get_url_prefix()
            );
    }
    
}