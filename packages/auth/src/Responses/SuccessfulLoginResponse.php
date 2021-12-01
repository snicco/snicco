<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use WP_User;
use Snicco\Http\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
class SuccessfulLoginResponse extends Response
{
    
    private WP_User $user;
    private bool    $remember;
    
    public function __construct(ResponseInterface $psr7_response, WP_User $user, bool $remember)
    {
        parent::__construct($psr7_response);
        $this->user = $user;
        $this->remember = $remember;
    }
    
    public function authenticateUser() :WP_User
    {
        return $this->user;
    }
    
    public function rememberUser() :bool
    {
        return $this->remember;
    }
    
}