<?php

namespace Snicco\Auth\Middleware;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;

class TwoFactorDisbaled extends Middleware
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