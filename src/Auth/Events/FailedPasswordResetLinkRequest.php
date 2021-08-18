<?php

namespace Snicco\Auth\Events;

use Snicco\Events\Event;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Fail2ban\Bannable;
use BetterWpHooks\Traits\IsAction;

class FailedPasswordResetLinkRequest extends Event implements Bannable
{
    
    use IsAction;
    
    private Request $request;
    private string  $failed_for_login;
    
    public function __construct(Request $request, string $failed_for_login)
    {
        $this->request = $request;
        $this->failed_for_login = $failed_for_login;
    }
    
    public function request() :Request
    {
        return $this->request;
    }
    
    public function priority() :int
    {
        return E_NOTICE;
    }
    
    public function fail2BanMessage() :string
    {
        return "User enumeration trying to request a new password for user login [$this->failed_for_login]";
    }
    
}