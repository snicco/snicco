<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Middleware;

use Snicco\SessionBundle\Keys;
use Snicco\Component\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;
use Snicco\Component\Session\SessionManager\SessionManager;

use function Snicco\SessionBundle\getSessionFromManager;

/**
 * @interal
 */
final class AllowMutableSession extends AbstractMiddleware
{
    
    /**
     * @var SessionManager
     */
    private $session_manager;
    
    public function __construct(SessionManager $session_manager)
    {
        $this->session_manager = $session_manager;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ( ! $request->isReadVerb()) {
            return $next($request);
        }
        
        if ($request->getAttribute(Keys::WRITE_SESSION) instanceof Session) {
            return $next($request);
        }
        
        $session = getSessionFromManager($request, $this->session_manager);
        
        return $next($request->withAttribute(Keys::WRITE_SESSION, $session));
    }
    
}