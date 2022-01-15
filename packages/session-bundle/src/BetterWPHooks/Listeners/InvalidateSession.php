<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\BetterWPHooks\Listeners;

use Snicco\HttpRouting\Http\Cookies;
use Snicco\Session\ValueObjects\CookiePool;
use Snicco\HttpRouting\Http\ResponseEmitter;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\SessionBundle\BetterWPHooks\Events\UserLoggedIn;
use Snicco\SessionBundle\BetterWPHooks\Events\UserLoggedOut;

use function Snicco\SessionBundle\sessionCookieToHttpCookie;

final class InvalidateSession
{
    
    /**
     * @var ResponseEmitter
     */
    private $emitter;
    
    /**
     * @var SessionManagerInterface
     */
    private $session_manager;
    
    public function __construct(ResponseEmitter $emitter, SessionManagerInterface $session_manager)
    {
        $this->emitter = $emitter;
        $this->session_manager = $session_manager;
    }
    
    /**
     * @note We are not inside a routing flow.
     */
    public function handleLogin(UserLoggedIn $login) :void
    {
        $session = $this->session_manager->start(
            CookiePool::fromSuperGlobals()
        );
        
        $session->rotate();
        
        $this->session_manager->save($session);
        
        $cookie = sessionCookieToHttpCookie($this->session_manager->toCookie($session));
        
        $this->emitter->emitCookies((new Cookies())->add($cookie));
    }
    
    /**
     * @note We are not in a routing flow. This method gets called on the wp_login/logout
     * event.
     * The Event Listener for this method gets unhooked when using the AUTH-Extension
     */
    public function handleLogout(UserLoggedOut $logout)
    {
        $session = $this->session_manager->start(
            CookiePool::fromSuperGlobals()
        );
        
        $session->invalidate();
        
        $this->session_manager->save($session);
        
        $cookie = sessionCookieToHttpCookie($this->session_manager->toCookie($session));
        
        $this->emitter->emitCookies((new Cookies())->add($cookie));
    }
    
}