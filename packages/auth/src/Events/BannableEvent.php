<?php

namespace Snicco\Auth\Events;

use Snicco\Http\Psr7\Request;
use Snicco\Auth\Fail2Ban\Bannable;
use Snicco\EventDispatcher\Events\CoreEvent;

abstract class BannableEvent extends CoreEvent implements Bannable
{
    
    protected Request $request;
    
    public function priority() :int
    {
        return LOG_WARNING;
    }
    
    abstract public function fail2BanMessage() :string;
    
    public function request() :Request
    {
        return $this->request;
    }
    
}