<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use WP_User;
use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Snicco\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Snicco\Contracts\ResponseableInterface;
use Snicco\Auth\Responses\SuccessfulLoginResponse;
use Snicco\Auth\Exceptions\FailedAuthenticationException;

abstract class Authenticator extends Middleware
{
    
    /**
     * @param  Request  $request
     * @param  Delegate  $next  This class can be called as a closure. $next($request)
     *
     * @return SuccessfulLoginResponse|ResponseInterface|string|array|ResponseableInterface
     * SuccessfulLoginResponse if authentication was successful | Anything that can be transformed
     *     to a response otherwise
     * @throws FailedAuthenticationException
     * @see ResponseFactory::toResponse()
     */
    abstract public function attempt(Request $request, $next);
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        
        return $this->response_factory->toResponse(
            $this->attempt($request, $next)
        );
        
    }
    
    protected function login(WP_User $user, bool $remember = false) :SuccessfulLoginResponse
    {
        return new SuccessfulLoginResponse(
            $this->response_factory->createResponse(),
            $user,
            $remember
        );
    }
    
}