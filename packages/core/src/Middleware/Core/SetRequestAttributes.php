<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;

class SetRequestAttributes extends AbstractMiddleware
{
    
    /**
     * @todo Define a UserIDProvider Interface
     */
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $request = $request
            ->withUserId(0);
        
        return $next($request);
    }
    
}