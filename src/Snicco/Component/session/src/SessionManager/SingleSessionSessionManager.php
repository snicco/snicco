<?php

declare(strict_types=1);

namespace Snicco\Component\Session\SessionManager;

use Snicco\Component\Session\Session;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\SessionCookie;

/**
 * Use this class if you want to only ever manage one session per request.
 * This class should not be used as a service locator to pass around a global session object.
 *
 * @api
 */
final class SingleSessionSessionManager implements SessionManager
{
    
    private SessionManager $session_manager;
    private Session        $session;
    
    public function __construct(SessionManager $session_manager)
    {
        $this->session_manager = $session_manager;
    }
    
    public function start(CookiePool $cookie_pool) :Session
    {
        if ( ! isset($this->session)) {
            $this->session = $this->session_manager->start($cookie_pool);
        }
        
        return $this->session;
    }
    
    public function save(Session $session) :void
    {
        $this->session_manager->save($session);
    }
    
    public function toCookie(ImmutableSession $session) :SessionCookie
    {
        return $this->session_manager->toCookie($session);
    }
    
}