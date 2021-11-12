<?php

namespace Snicco\Auth\Events;

use Snicco\Http\Psr7\Request;

class FailedAuthConfirmation extends BannableEvent
{
    
    private int $for_user;
    
    public function __construct(Request $request, int $for_user)
    {
        $this->request = $request;
        $this->for_user = $for_user;
    }
    
    public function fail2BanMessage() :string
    {
        return "Failed auth confirmation for user [$this->for_user]";
    }
    
    public function forUser() :int
    {
        return $this->for_user;
    }
    
}