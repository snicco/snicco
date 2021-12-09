<?php

declare(strict_types=1);

namespace Snicco\Auth\Middleware;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Snicco\Core\Routing\UrlGenerator;
use Psr\Http\Message\ResponseInterface;

class AuthUnconfirmed extends Middleware
{
    
    private UrlGenerator $url;
    
    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $session = $request->session();
        
        if ($session->hasValidAuthConfirmToken()) {
            return $this->response_factory->redirect()->back(
                302,
                $this->url->toRoute('dashboard')
            );
        }
        
        return $next($request);
    }
    
}