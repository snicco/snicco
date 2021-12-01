<?php

declare(strict_types=1);

namespace Snicco\Middleware;

use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
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
            
            return $this->response_factory->redirectToRoute('dashboard');
        }
        
        return $next($request);
    }
    
}
