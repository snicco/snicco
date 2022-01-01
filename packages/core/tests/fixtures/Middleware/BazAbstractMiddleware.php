<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Middleware;

use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;

class BazAbstractMiddleware extends AbstractMiddleware
{
    
    private string $baz;
    
    public function __construct($baz = 'baz')
    {
        $this->baz = $baz;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if (isset($request->body)) {
            $request->body .= $this->baz;
            
            return $next($request);
        }
        
        $request->body = $this->baz;
        
        return $next($request);
    }
    
}