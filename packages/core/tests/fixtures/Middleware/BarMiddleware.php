<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Middleware;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class BarMiddleware extends Middleware
{
    
    private string $bar;
    
    public function __construct($bar = 'bar')
    {
        $this->bar = $bar;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if (isset($request->body)) {
            $request->body .= $this->bar;
            
            return $next($request);
        }
        
        $request->body = $this->bar;
        
        return $next($request);
    }
    
}