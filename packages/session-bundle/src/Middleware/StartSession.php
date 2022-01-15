<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Middleware;

use RuntimeException;
use Snicco\SessionBundle\Keys;
use Snicco\Session\ImmutableSession;
use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Middleware\Delegate;
use Snicco\HttpRouting\Http\AbstractMiddleware;
use Snicco\Session\Contracts\SessionManagerInterface;

use function rtrim;
use function sprintf;
use function Snicco\SessionBundle\getSessionFromManager;

/**
 * @interal
 */
final class StartSession extends AbstractMiddleware
{
    
    /**
     * @var string
     */
    private $cookie_path;
    
    /**
     * @var SessionManagerInterface
     */
    private $session_manager;
    
    public function __construct(string $cookie_path, SessionManagerInterface $session_manager)
    {
        $this->cookie_path = $cookie_path.'*';
        $this->session_manager = $session_manager;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $this->compatibleCookiePath($request);
        
        $session = getSessionFromManager($request, $this->session_manager);
        
        // All routes should have read access to the session
        $request = $request->withAttribute(
            Keys::READ_SESSION,
            ImmutableSession::fromSession($session)
        );
        
        // Non-Read methods should not have write-access to the current session.
        if ( ! $request->isReadVerb()) {
            $request = $request->withAttribute(
                Keys::WRITE_SESSION,
                $session
            );
        }
        
        return $next($request);
    }
    
    /**
     * @throws RuntimeException
     */
    private function compatibleCookiePath(Request $request) :void
    {
        if ( ! $request->pathIs($this->cookie_path)) {
            throw new RuntimeException(
                sprintf(
                    'The request path [%s] is not compatible with your cookie path [%s].',
                    $request->path(),
                    rtrim($this->cookie_path, '*')
                )
            );
        }
    }
    
}