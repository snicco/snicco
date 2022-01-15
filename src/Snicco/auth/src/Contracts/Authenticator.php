<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use WP_User;
use Psr\Http\Message\ResponseInterface;
use Snicco\Auth\Responses\SuccessfulLoginResponse;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;
use Snicco\Component\HttpRouting\Http\Responses\NullResponse;

abstract class Authenticator extends AbstractMiddleware
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