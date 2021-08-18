<?php

namespace Snicco\Auth\Events;

use Snicco\Http\Psr7\Request;

class FailedTwoFactorAuthentication extends BannableEvent
{
    
    private int $user_id;
    
    public function __construct(Request $request, int $user_id)
    {
        $this->request = $request;
        $this->user_id = $user_id;
    }
    
    public function fail2BanMessage() :string
    {
        return "Failed two-factor authentication for user [$this->user_id]";
    }
    
    public function userId() :int
    {
        return $this->user_id;
    }
    
}