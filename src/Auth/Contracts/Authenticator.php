<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use WP_User;
use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Http\Responses\NullResponse;
use Snicco\Auth\Responses\SuccessfulLoginResponse;

abstract class Authenticator extends Middleware
{
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        return $this->response_factory->toResponse(
            $this->attempt($request, $next)
        );
    }
    
    /**
     * @param  Request  $request
     * @param  Delegate  $next
     * $next can be called as a closure $next($request) to delegate to the next authenticator
     *
     * @return SuccessfulLoginResponse|NullResponse
     */
    abstract public function attempt(Request $request, $next);
    
    protected function login(WP_User $user, bool $remember = false) :SuccessfulLoginResponse
    {
        return new SuccessfulLoginResponse(
            $this->response_factory->make(),
            $user,
            $remember
        );
    }
    
    protected function unauthenticated() :NullResponse
    {
        return $this->response_factory->null();
    }
    
}