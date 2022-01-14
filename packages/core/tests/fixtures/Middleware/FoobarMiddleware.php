<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Middleware;

use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\AbstractMiddleware;

class FoobarMiddleware extends AbstractMiddleware
{
    
    private string $val;
    
    public function __construct(string $foo = null, string $bar = null)
    {
        if ( ! $foo && ! $bar) {
            $this->val = 'foobar_middleware';
        }
        else {
            $this->val = $foo.'_'.($bar ? : 'foobar_middleware');
        }
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        $response = $next($request);
        
        $response->getBody()->write(':'.$this->val);
        return $response;
    }
    
}