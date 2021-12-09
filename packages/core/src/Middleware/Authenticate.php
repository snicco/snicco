<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Support\WP;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class Authenticate extends Middleware
{
    
    private ?string $url;
    
    public function __construct(?string $url = null)
    {
        $this->url = $url;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if (WP::isUserLoggedIn()) {
            return $next($request);
        }
        
        if ($request->isExpectingJson()) {
            return $this->response_factory
                ->json('Authentication Required')
                ->withStatus(401);
        }
        
        $redirect_after_login = $this->url ?? $request->fullPath();
        
        return $this->response_factory->redirect()->toLogin($redirect_after_login, true);
    }
    
}
