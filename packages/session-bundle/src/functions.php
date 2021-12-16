<?php

declare(strict_types=1);

namespace Snicco\SessionBundle
{
    
    use LogicException;
    use Snicco\Core\Http\Cookie;
    use Snicco\Core\Http\Psr7\Request;
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
        $cookies = $request->cookies()->all();
        return $session_manager->start(new CookiePool($cookies));
    }
    
    /**
     * @interal
     */
    function sessionCookieToHttpCookie(SessionCookie $session_cookie) :Cookie
    {
        $http_cookie = new Cookie($session_cookie->name(), $session_cookie->value());
        $http_cookie->path($session_cookie->path());
        $http_cookie->domain($session_cookie->domain());
        
        if ( ! $session_cookie->httpOnly()) {
            $http_cookie->allowJs();
        }
        
        if ( ! $session_cookie->secureOnly()) {
            $http_cookie->allowUnsecure();
        }
        
        $http_cookie->sameSite($session_cookie->sameSite());
        $http_cookie->expires($session_cookie->expiryTimestamp());
        
        return $http_cookie;
    }
}