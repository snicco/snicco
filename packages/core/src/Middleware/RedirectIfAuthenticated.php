<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Support\WP;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

class RedirectIfAuthenticated extends AbstractMiddleware
{
    
    private ?string $path;
    
    public function __construct(string $path = null)
    {
        $this->path = $path;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if (WP::isUserLoggedIn()) {
            if ($request->isExpectingJson()) {
                return $this->respond()
                            ->json(['message' => 'Only guests can access this route.'], 403);
            }
            
            if ($this->path) {
                return $this->redirect()->to($this->path);
            }
            else {
                try {
                    return $this->redirect()->toRoute('dashboard');
                } catch (RouteNotFound $e) {
                    return $this->redirect()->home();
                }
            }
        }
        
        return $next($request);
    }
    
}
