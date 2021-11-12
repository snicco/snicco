<?php

namespace Snicco\Auth\Middleware;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
use Snicco\ExceptionHandling\Exceptions\AuthorizationException;

class TwoFactorEnabled extends Middleware
{
    
    use InteractsWithTwoFactorSecrets;
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ($this->userHasTwoFactorEnabled($request->user())) {
            return $next($request);
        }
        
        $e = new AuthorizationException(
            "Missing 2FA settings for user [{$request->user()->ID}] while trying to access [{$request->path()}] with method [{$request->getMethod()}].]"
        );
        $e->withMessageForUsers(
            'Two-Factor-Authentication needs to be enabled for your account to perform this action.'
        );
        
        throw $e;
    }
    
}