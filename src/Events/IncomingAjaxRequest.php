<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Support\Arr;
use BetterWpHooks\Traits\IsAction;
use BetterWpHooks\Traits\DispatchesConditionally;

class IncomingAjaxRequest extends IncomingRequest
{
    
    use DispatchesConditionally;
    use IsAction;
    
    public function shouldDispatch() :bool
    {
        if ( ! $this->request->isWpAjax()) {
            return false;
        }
        
        if ($this->request->isReadVerb()) {
            return Arr::has($this->request->getQueryParams(), 'action');
        }
        
        return Arr::has($this->request->getParsedBody(), 'action');
    }
    
}