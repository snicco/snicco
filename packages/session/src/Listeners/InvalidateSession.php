<?php

declare(strict_types=1);

namespace Snicco\Session\Listeners;

use Snicco\Http\Cookies;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseEmitter;
use Snicco\Session\SessionManager;
use Snicco\Session\Events\NewLogin;
use Snicco\Session\Events\NewLogout;

final class InvalidateSession
{
    
    private Request         $request;
    private ResponseEmitter $emitter;
    private SessionManager  $session_manager;
    private Session         $session;
    
    public function __construct(Request $request, ResponseEmitter $emitter, SessionManager $session_manager, Session $session)
    {
        $this->request = $request;
        $this->emitter = $emitter;
        $this->session_manager = $session_manager;
        $this->session = $session;
    }
    
    /**
     * @note We are not in a routing flow. This method gets called on the wp_login
     * event.
     * The Event Listener for this method gets unhooked when using the AUTH-Extension
     */
    public function handleLogin(NewLogin $login)
    {
        $this->session_manager->start($this->request, $login->user->ID);
        
        $this->session->regenerate();
        $this->session->save();
        
        $cookie = $this->session_manager->sessionCookie();
        
        $this->emitter->emitCookies((new Cookies())->add($cookie));
    }
    
    /**
     * @note We are not in a routing flow. This method gets called on the wp_login/logout
     * event.
     * The Event Listener for this method gets unhooked when using the AUTH-Extension
     */
    public function handleLogout(NewLogout $logout)
    {
        $this->session_manager->start($this->request, 0);
        $this->session->invalidate();
        $this->session->save();
        
        $cookie = $this->session_manager->sessionCookie();
        
        $this->emitter->emitCookies((new Cookies())->add($cookie));
    }
    
}