<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Support\WP;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class RedirectIfAuthenticated extends Middleware
{
    
    private ?string $url;
    
    public function __construct(string $url = null)
    {
        $this->url = $url;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if (WP::isUserLoggedIn()) {
            if ($request->isExpectingJson()) {
                return $this->response_factory
                    ->json(['message' => 'Only guests can access this route.'])
                    ->withStatus(403);
            }
            
            if ($this->url) {
                return $this->response_factory->redirect()
                                              ->to($this->url);
            }
            
            return $this->response_factory->redirect()->toRoute('dashboard');
        }
        
        return $next($request);
    }
    
}
