<?php

namespace Snicco\Auth\Events;

use Snicco\Events\Event;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Fail2Ban\Bannable;
use BetterWpHooks\Traits\IsAction;

abstract class BannableEvent extends Event implements Bannable
{
    
    use IsAction;
    
    protected Request $request;
    
    public function priority() :int
    {
        return E_WARNING;
    }
    
    abstract public function fail2BanMessage() :string;
    
    public function request() :Request
    {
        return $this->request;
    }
    
}