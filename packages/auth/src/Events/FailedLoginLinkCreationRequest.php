<?php

namespace Snicco\Auth\Events;

use Snicco\Http\Psr7\Request;

class FailedLoginLinkCreationRequest extends BannableEvent
{
    
    private string $login;
    
    public function __construct(Request $request, string $login)
    {
        $this->request = $request;
        $this->login = $login;
    }
    
    public function fail2BanMessage() :string
    {
        return "User enumeration trying to request a new magic link for user login [$this->login]";
    }
    
    public function login() :string
    {
        return $this->login;
    }
    
}