<?php

declare(strict_types=1);

namespace Snicco\Auth\Middleware;

use Snicco\Http\Delegate;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class ConfirmAuth extends Middleware
{
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $session = $request->session();
        
        if ( ! $session->hasValidAuthConfirmToken()) {
            $this->setIntendedUrl($request, $session);
            $session->remove('auth.confirm');
            return $this->response_factory->redirect()->toRoute('auth.confirm');
        }
        
        return $next($request);
    }
    
    private function setIntendedUrl(Request $request, Session $session)
    {
        if ($request->isGet() && ! $request->isAjax()) {
            $session->setIntendedUrl($request->fullPath());
            
            return;
        }
        
        $session->setIntendedUrl($session->getPreviousUrl());
    }
    
}