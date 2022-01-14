<?php

declare(strict_types=1);

namespace Snicco\Auth\Middleware;

use Snicco\Session\Session;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Delegate;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\AbstractMiddleware;

use function Snicco\SessionBundle\getWriteSession;

class ConfirmAuth extends AbstractMiddleware
{
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $session = getWriteSession($request);
        
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
            $session->setIntendedUrl($request->fullRequestTarget());
            
            return;
        }
        
        $session->setIntendedUrl($session->getPreviousUrl());
    }
    
}