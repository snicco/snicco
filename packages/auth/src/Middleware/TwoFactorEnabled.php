<?php

namespace Snicco\Auth\Middleware;

use Snicco\Core\Routing\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\AbstractMiddleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
use Snicco\Core\ExceptionHandling\Exceptions\AuthorizationException;

class TwoFactorEnabled extends AbstractMiddleware
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