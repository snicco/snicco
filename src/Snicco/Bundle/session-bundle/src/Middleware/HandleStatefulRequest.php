<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Middleware;

use RuntimeException;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Snicco\SessionBundle\Keys;
use Snicco\Component\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;
use Snicco\Component\Session\Exception\CantDestroySession;
use Snicco\Component\Session\SessionManager\SessionManager;

use function sprintf;
use function Snicco\SessionBundle\sessionCookieToHttpCookie;

/**
 * @interal
 */
final class HandleStatefulRequest extends AbstractMiddleware
{
    
    /**
     * @var SessionManager
     */
    private $session_manager;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(SessionManager $session_manager, LoggerInterface $logger = null)
    {
        $this->session_manager = $session_manager;
        $this->logger = $logger ?? new NullLogger();
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        // The session may or may not have been shared with the current request,
        // but it's not relevant to us here.
        // The actual logic that determines if a session should be saved lives inside the
        // SessionManager and is not a concern here.
        $read_session = $request->getAttribute(Keys::READ_SESSION);
        
        if ( ! $read_session instanceof ImmutableSession) {
            throw new RuntimeException(
                sprintf(
                    "A read-only session has not been shared with the request.\nMake sure that the [%s] middleware is running before the [%s] middleware.",
                    StartSession::class,
                    HandleStatefulRequest::class,
                )
            );
        }
        
        $write_session = $request->getAttribute(Keys::WRITE_SESSION);
        
        $response = $next($request);
        
        if ($write_session instanceof Session) {
            try {
                $this->session_manager->save($write_session);
            } catch (CantDestroySession $e) {
                $this->logger->error(
                    "Session garbage collection did not work.",
                    ['exception' => $e]
                );
            }
        }
        
        return $this->addCookieToResponse(
            $response,
            $read_session,
        );
    }
    
    private function addCookieToResponse(Response $response, ImmutableSession $read_session) :Response
    {
        $session_cookie = $this->session_manager->toCookie($read_session);
        
        $http_cookie = sessionCookieToHttpCookie($session_cookie);
        
        return $response->withCookie($http_cookie);
    }
    
}