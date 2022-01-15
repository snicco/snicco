<?php

namespace Snicco\Auth\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Middleware\Delegate;
use Snicco\HttpRouting\Http\AbstractMiddleware;
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