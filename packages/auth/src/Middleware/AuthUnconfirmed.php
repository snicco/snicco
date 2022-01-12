<?php

declare(strict_types=1);

namespace Snicco\Auth\Middleware;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Routing\UrlGenerator\InternalUrlGenerator;

class AuthUnconfirmed extends AbstractMiddleware
{
    
    private InternalUrlGenerator $url;
    
    public function __construct(InternalUrlGenerator $url)
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