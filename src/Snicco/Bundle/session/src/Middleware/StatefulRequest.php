<?php

declare(strict_types=1);

namespace Snicco\Bundle\Session\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Snicco\Bundle\Session\Event\WPLogin;
use Snicco\Component\HttpRouting\Http\Cookie;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\Session\Exception\CouldNotDestroySessions;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\MutableSession;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\SessionManager\SessionManager;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\ReadOnlySession;
use Snicco\Component\Session\ValueObject\SessionCookie;

use function ltrim;
use function setcookie;

final class StatefulRequest extends Middleware
{
    /**
     * @var string
     */
    public const ALLOW_WRITE_SESSION_FOR_READ_VERBS = '_stateful_request.allow_write';

    private SessionManager $session_manager;

    private LoggerInterface $logger;

    private string $cookie_path;

    private bool $is_handling_request = false;

    private bool $invalidate_next_session = false;

    private bool $rotate_next_session = false;

    public function __construct(SessionManager $session_manager, LoggerInterface $logger, string $cookie_path)
    {
        $this->session_manager = $session_manager;
        $this->logger = $logger;
        $this->cookie_path = '/' . ltrim($cookie_path, '/') . '*';
    }

    public function wpLogoutEvent(): void
    {
        if ($this->is_handling_request) {
            $this->invalidate_next_session = true;

            return;
        }

        // A user got logged out by WordPress. We have to invalidate the session if one is currently active.
        $session = $this->session_manager->start(CookiePool::fromSuperGlobals());

        /*
         * The session is newly created which means that either the user has no current active session of the session
         * cookie was not sent.
         * In both cases we can't/don't have to do anything.
         */
        if ($session->isNew()) {
            return;
        }

        $session->invalidate();
        $this->session_manager->save($session);
        $this->emitCookie($this->session_manager->toCookie($session));
    }

    public function wpLoginEvent(WPLogin $event): void
    {
        if ($this->is_handling_request) {
            $this->rotate_next_session = true;

            return;
        }

        // A user got logged in by WordPress. We have to rotate the session id.
        $session = $this->session_manager->start(CookiePool::fromSuperGlobals());

        /*
         * The session is newly created which means that either the user has no current active session of the session
         * cookie was not sent.
         * In both cases we can't/don't have to do anything.
         */
        if ($session->isNew()) {
            return;
        }

        $session->setUserId($event->user->ID);
        $session->rotate();

        $this->session_manager->save($session);
        $this->emitCookie($this->session_manager->toCookie($session));
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $this->validateCookiePath($request);
        $this->deleteStaleSessions();

        $session = $this->startSession($request);

        $request = $this->withSession($request, $session);

        /*
         * We need to keep track if we are currently handling a request. Otherwise, we will get inconsistent behaviour
         * if the developer logs out/logs in a user during this request-response cycle.
         */
        $this->is_handling_request = true;

        $response = $next($request);

        $this->is_handling_request = false;

        $this->maybeInvalidateOrRotate($session);

        $this->session_manager->save($session);

        return $this->addSessionCookie($response, $this->session_manager->toCookie($session));
    }

    private function maybeInvalidateOrRotate(Session $session): void
    {
        if ($this->invalidate_next_session) {
            $session->invalidate();
            $this->invalidate_next_session = false;
        } elseif ($this->rotate_next_session) {
            $session->rotate();
            $this->rotate_next_session = false;
        }
    }

    private function withSession(Request $request, Session $session): Request
    {
        $request = $request->withAttribute(ImmutableSession::class, ReadOnlySession::fromSession($session));

        if ($request->isReadVerb() && ! $request->getAttribute(self::ALLOW_WRITE_SESSION_FOR_READ_VERBS)) {
            return $request;
        }

        return $request->withAttribute(MutableSession::class, $session);
    }

    private function emitCookie(SessionCookie $cookie): void
    {
        $same_site = $cookie->sameSite();
        $same_site = ('None; Secure' === $same_site) ? 'None' : $same_site;

        setcookie($cookie->name(), $cookie->value(), [
            'expires' => $cookie->expiryTimestamp(),
            'samesite' => $same_site,
            'secure' => $cookie->secureOnly(),
            'path' => $cookie->path(),
            'httponly' => $cookie->httpOnly(),
        ]);
    }

    private function startSession(Request $request): Session
    {
        /** @var array<string,string> $cookies */
        $cookies = $request->getCookieParams();

        $session = $this->session_manager->start(new CookiePool($cookies));

        if ($session->userId()) {
            return $session;
        }

        $user_id = $request->userId();

        if ($user_id) {
            $session->setUserId($user_id);
        }

        return $session;
    }

    private function validateCookiePath(Request $request): void
    {
        if (! $request->pathIs($this->cookie_path)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The request path [%s] is not compatible with your session cookie path [%s].',
                    $request->path(),
                    rtrim($this->cookie_path, '*')
                )
            );
        }
    }

    private function addSessionCookie(Response $response, SessionCookie $session_cookie): Response
    {
        $http_cookie = (new Cookie($session_cookie->name(), $session_cookie->value()));

        if (! $session_cookie->httpOnly()) {
            $http_cookie = $http_cookie->withJsAccess();
        }

        if (! $session_cookie->secureOnly()) {
            $http_cookie = $http_cookie->withUnsecureHttp();
        }

        $http_cookie = $http_cookie->withSameSite($session_cookie->sameSite());

        $expires_at = $session_cookie->expiryTimestamp();

        if (0 !== $expires_at) {
            $http_cookie = $http_cookie->withExpiryTimestamp($expires_at);
        }

        $http_cookie = $http_cookie->withDomain($session_cookie->domain());
        $http_cookie = $http_cookie->withPath($session_cookie->path());

        return $response->withCookie($http_cookie);
    }

    private function deleteStaleSessions(): void
    {
        try {
            $this->session_manager->gc();
        } catch (CouldNotDestroySessions $e) {
            $this->logger->log(LogLevel::ERROR, 'Garbage collection failed.', [
                'exception' => $e,
            ]);
        }
    }
}
