<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Support\WP;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;

class Authenticate extends AbstractMiddleware
{
    
    private ?string $path;
    
    public function __construct(?string $path = null)
    {
        $this->path = $path;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if (WP::isUserLoggedIn()) {
            return $next($request);
        }
        
        if ($request->isExpectingJson()) {
            return $this->respond()
                        ->json('Authentication Required', 401);
        }
        
        $redirect_after_login = $this->path ?? $request->getUri()->__toString();
        
        $location = WP::loginUrl($redirect_after_login, true);
        
        return $this->respond()->redirect($location);
    }
    
}
