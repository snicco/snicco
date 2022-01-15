<?php

declare(strict_types=1);

namespace Tests\HttpRouting\fixtures;

use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\AbstractMiddleware;

final class BooleanMiddleware extends AbstractMiddleware
{
    
    private string $val;
    
    public function __construct(bool $val)
    {
        $this->val = 'boolean_'.($val ? 'true' : 'false');
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        $response = $next($request);
        
        $response->getBody()->write(':'.$this->val);
        return $response;
    }
    
}