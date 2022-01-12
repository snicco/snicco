<?php

namespace Snicco\Auth\Middleware;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Delegate;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;

class TwoFactorDisbaled extends AbstractMiddleware
{
    
    use InteractsWithTwoFactorSecrets;
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ($this->userHasTwoFactorEnabled($request->user())) {
            return $this->response_factory->json(
                [
                    'message' => 'Two-Factor authentication is already enabled.',
                ],
                409
            );
        }
        
        return $next($request);
    }
    
}