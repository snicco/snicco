<?php

declare(strict_types=1);

namespace Tests\HttpRouting\fixtures;

use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\AbstractMiddleware;

class BarMiddleware extends AbstractMiddleware
{
    
    private string $bar;
    
    public function __construct($bar = 'bar_middleware')
    {
        $this->bar = $bar;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        $response = $next($request);
        
        $response->getBody()->write(':'.$this->bar);
        return $response;
    }
    
}