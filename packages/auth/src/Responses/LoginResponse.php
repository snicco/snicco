<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use WP_User;
use Snicco\Http\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class LoginResponse extends Response
{
    
    private WP_User $user;
    private bool    $remember;
    
    public function __construct(ResponseInterface $psr7_response, WP_User $user, bool $remember = false)
    {
        parent::__construct(
            $psr7_response
        );
        $this->user = $user;
        $this->remember = $remember;
    }
    
    public function user() :WP_User
    {
        return $this->user;
    }
    
    public function shouldRememberUser() :bool
    {
        return $this->remember;
    }
    
}