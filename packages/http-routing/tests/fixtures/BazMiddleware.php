<?php

declare(strict_types=1);

namespace Tests\HttpRouting\fixtures;

use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\AbstractMiddleware;

class BazMiddleware extends AbstractMiddleware
{
    
    private string $baz;
    
    public function __construct($baz = 'baz_middleware')
    {
        $this->baz = $baz;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        $response = $next($request);
        
        $response->getBody()->write(':'.$this->baz);
        return $response;
    }
    
}