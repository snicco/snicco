<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use WP_User;
use Snicco\Auth\Traits\UsesCurrentRequest;
use Snicco\Contracts\Responsable;

abstract class AbstractLoginResponse implements Responsable
{
    
    protected bool $remember_user = false;
    
    use UsesCurrentRequest;
    
    public function rememberUser(bool $true = true) :AbstractLoginResponse
    {
        $this->remember_user = $true;
        return $this;
    }
    
    public function shouldRememberUser() :bool
    {
        return $this->remember_user;
    }
    
    public function user() :WP_User
    {
        return $this->user;
    }
    
}