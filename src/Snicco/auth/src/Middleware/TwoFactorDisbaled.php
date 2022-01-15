<?php

namespace Snicco\Auth\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

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