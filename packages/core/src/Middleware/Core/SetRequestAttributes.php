<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Support\WP;
use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class SetRequestAttributes extends Middleware
{
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $request = $request
            ->withUserId(WP::userId());
        
        return $next($request);
    }
    
}