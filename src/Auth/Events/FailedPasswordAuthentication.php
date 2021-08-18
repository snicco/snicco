<?php

namespace Snicco\Auth\Events;

use Snicco\Events\Event;
use Snicco\Http\Psr7\Request;
use Snicco\Auth\Fail2Ban\Bannable;
use BetterWpHooks\Traits\IsAction;

class FailedPasswordAuthentication extends Event implements Bannable
{
    
    use IsAction;
    
    private Request $request;
    private string  $login;
    private string  $password;
    private ?int    $user_id;
    
    public function __construct(Request $request, string $login, string $password = '', int $user_id = null)
    {
        
        $this->request = $request;
        $this->login = $login;
        $this->password = $password;
        $this->user_id = $user_id;
        
    }
    
    public function priority() :int
    {
        return E_WARNING;
    }
    
    public function fail2BanMessage() :string
    {
        if (is_null($this->user_id) || $this->user_id === 0) {
            return "Failed authentication attempt for unknown user_login [$this->login]";
        }
        
        return "Failed authentication attempt for user [$this->user_id] with invalid password [$this->password]";
        
    }
    
    public function request() :Request
    {
        return $this->request;
    }
    
    public function user_id() :?int
    {
        return $this->user_id;
    }
    
    public function password() :string
    {
        return $this->password;
    }
    
    public function login() :string
    {
        return $this->login;
    }
    
}