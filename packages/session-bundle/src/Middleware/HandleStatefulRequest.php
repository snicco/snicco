<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Middleware;

use RuntimeException;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Snicco\Core\Http\Delegate;
use Snicco\SessionBundle\Keys;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Session\Contracts\SessionInterface;
use Snicco\Session\Exceptions\CantDestroySession;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\Session\Contracts\ImmutableSessionInterface;

use function sprintf;
use function Snicco\SessionBundle\sessionCookieToHttpCookie;

/**
 * @interal
 */
final class HandleStatefulRequest extends Middleware
{
    
    /**
     * @var SessionManagerInterface
     */
    private $session_manager;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(SessionManagerInterface $session_manager, LoggerInterface $logger = null)
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
        
        if ( ! $read_session instanceof ImmutableSessionInterface) {
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
        
        if ($write_session instanceof SessionInterface) {
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
    
    private function addCookieToResponse(Response $response, ImmutableSessionInterface $read_session) :Response
    {
        $session_cookie = $this->session_manager->toCookie($read_session);
        
        $http_cookie = sessionCookieToHttpCookie($session_cookie);
        
        return $response->withCookie($http_cookie);
    }
    
}