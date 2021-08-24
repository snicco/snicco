<?php

namespace Snicco\Auth\Events;

use Snicco\Http\Psr7\Request;

class FailedPasswordResetLinkRequest extends BannableEvent
{
    
    private string $failed_for_login;
    
    public function __construct(Request $request, string $failed_for_login)
    {
        $this->request = $request;
        $this->failed_for_login = $failed_for_login;
    }
    
    public function priority() :int
    {
        return LOG_NOTICE;
    }
    
    public function fail2BanMessage() :string
    {
        return "User enumeration trying to request a new password for user login [$this->failed_for_login]";
    }
    
}