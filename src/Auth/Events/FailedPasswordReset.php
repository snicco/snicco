<?php

namespace Snicco\Auth\Events;

use Snicco\Events\Event;
use Snicco\Http\Psr7\Request;
use BetterWpHooks\Traits\IsAction;

class FailedPasswordReset extends Event
{
    
    use IsAction;
    
    private Request $request;
    
    public function __construct(Request $request)
    {
        
        $this->request = $request;
    }
    
    public function request() :Request
    {
        return $this->request;
    }
    
}