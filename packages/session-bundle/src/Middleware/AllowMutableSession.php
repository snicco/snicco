<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Middleware;

use Snicco\SessionBundle\Keys;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Delegate;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\AbstractMiddleware;
use Snicco\Session\Contracts\SessionInterface;
use Snicco\Session\Contracts\SessionManagerInterface;

use function Snicco\SessionBundle\getSessionFromManager;

/**
 * @interal
 */
final class AllowMutableSession extends AbstractMiddleware
{
    
    /**
     * @var SessionManagerInterface
     */
    private $session_manager;
    
    public function __construct(SessionManagerInterface $session_manager)
    {
        $this->session_manager = $session_manager;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ( ! $request->isReadVerb()) {
            return $next($request);
        }
        
        if ($request->getAttribute(Keys::WRITE_SESSION) instanceof SessionInterface) {
            return $next($request);
        }
        
        $session = getSessionFromManager($request, $this->session_manager);
        
        return $next($request->withAttribute(Keys::WRITE_SESSION, $session));
    }
    
}