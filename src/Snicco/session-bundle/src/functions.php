<?php

declare(strict_types=1);

namespace Snicco\SessionBundle
{
    
    use LogicException;
    use Snicco\HttpRouting\Http\Cookie;
    use Snicco\HttpRouting\Http\Psr7\Request;
    use Snicco\Session\ValueObjects\CookiePool;
    use Snicco\Session\Contracts\SessionInterface;
    use Snicco\Session\ValueObjects\SessionCookie;
    use Snicco\Session\Contracts\SessionManagerInterface;
    use Snicco\Session\Contracts\ImmutableSessionInterface;
    
    /**
     * @throws LogicException
     * @api
     */
    function getReadSession(Request $request) :ImmutableSessionInterface
    {
        $session = $request->getAttribute(Keys::READ_SESSION);
        if ( ! $session instanceof ImmutableSessionInterface) {
            throw new LogicException("No read-only session has been shared with the request.");
        }
        return $session;
    }
    
    /**
     * @throws LogicException
     * @api
     */
    function getWriteSession(Request $request) :SessionInterface
    {
        $session = $request->getAttribute(Keys::WRITE_SESSION);
        if ( ! $session instanceof SessionInterface) {
            throw new LogicException("No writable session has been shared with the request.");
        }
        return $session;
    }
    
    /**
     * @interal
     */
    function getSessionFromManager(Request $request, SessionManagerInterface $session_manager) :SessionInterface
    {
        $cookies = $request->cookies()->toArray();
        return $session_manager->start(new CookiePool($cookies));
    }
    
    /**
     * @interal
     */
    function sessionCookieToHttpCookie(SessionCookie $session_cookie) :Cookie
    {
        $http_cookie = new Cookie($session_cookie->name(), $session_cookie->value());
        $http_cookie = $http_cookie->withPath($session_cookie->path());
        $http_cookie = $http_cookie->withDomain($session_cookie->domain());
        
        if ( ! $session_cookie->httpOnly()) {
            $http_cookie = $http_cookie->withJsAccess();
        }
        
        if ( ! $session_cookie->secureOnly()) {
            $http_cookie = $http_cookie->withUnsecureHttp();
        }
        
        $http_cookie = $http_cookie->withSameSite($session_cookie->sameSite());
        return $http_cookie->withExpiryTimestamp($session_cookie->expiryTimestamp());
    }
}