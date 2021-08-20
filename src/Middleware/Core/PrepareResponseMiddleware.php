<?php

namespace Snicco\Middleware\Core;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Snicco\Http\ResponsePreparation;
use Psr\Http\Message\ResponseInterface;

class PrepareResponseMiddleware extends Middleware
{
    
    private ResponsePreparation $preparation;
    
    public function __construct(ResponsePreparation $preparation)
    {
        $this->preparation = $preparation;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        return $this->preparation->prepare($response, $request);
    }
    
}