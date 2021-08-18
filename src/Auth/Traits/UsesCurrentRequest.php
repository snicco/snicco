<?php

declare(strict_types=1);

namespace Snicco\Auth\Traits;

use WP_User;
use Snicco\Http\Psr7\Request;

trait UsesCurrentRequest
{
    
    protected ?Request $request = null;
    protected ?WP_User $user    = null;
    
    public function forRequest(Request $request) :self
    {
        $this->request = $request;
        return $this;
    }
    
    public function forUser(WP_User $user) :self
    {
        $this->user = $user;
        return $this;
    }
    
}