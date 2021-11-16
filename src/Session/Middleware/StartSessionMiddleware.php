<?php

declare(strict_types=1);

namespace Snicco\Session\Middleware;

use Snicco\Http\Delegate;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Snicco\Http\Responses\NullResponse;
use Psr\Http\Message\ResponseInterface;
use Snicco\Http\Responses\InvalidResponse;
use Snicco\Http\Responses\DelegatedResponse;
use Snicco\Session\Contracts\SessionManagerInterface;

/**
 * @todo Think about what happens if the session cookie path is not set to the root domain.
 */
class StartSessionMiddleware extends Middleware
{
    
    private SessionManagerInterface $manager;
    
    public function __construct(SessionManagerInterface $manager)
    {
        $this->manager = $manager;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $this->manager->collectGarbage();
        
        $session = $this->manager->start($request, $request->userId());
        
        return $this->handleStatefulRequest($request, $session, $next);
    }
    
    private function handleStatefulRequest(Request $request, Session $session, Delegate $next) :ResponseInterface
    {
        $request = $request->withSession($session);
        
        $response = $next($request);
        
        $this->storePreviousUrl($response, $request, $session);
        $this->saveSession($response);
        
        return $response->withCookie($this->manager->sessionCookie());
    }
    
    private function saveSession(ResponseInterface $response) :void
    {
        if ( ! $response instanceof DelegatedResponse
             && ! $response instanceof NullResponse
             && ! $response instanceof InvalidResponse) {
            $this->manager->save();
        }
        else {
            add_action('shutdown', function () {
                $this->manager->save();
            });
        }
    }
    
    private function storePreviousUrl(ResponseInterface $response, Request $request, Session $session)
    {
        if ($response instanceof NullResponse || $response instanceof InvalidResponse) {
            return;
        }
        
        if ($request->isGet() && ! $request->isAjax()) {
            $session->setPreviousUrl($request->fullUrl());
        }
    }
    
}