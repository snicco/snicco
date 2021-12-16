<?php

declare(strict_types=1);

namespace Snicco\Session;

use Snicco\Session\ValueObjects\CookiePool;
use Snicco\Session\Contracts\SessionInterface;
use Snicco\Session\ValueObjects\SessionCookie;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\Session\Contracts\ImmutableSessionInterface;

/**
 * @api Use this class if you want to only ever manage one session per request.
 * Try hard to not use this class a service locator.
 */
final class SingleSessionSessionManager implements SessionManagerInterface
{
    
    /**
     * @var SessionManagerInterface
     */
    private $session_manager;
    
    /**
     * @var SessionInterface
     */
    private $session;
    
    public function __construct(SessionManagerInterface $session_manager)
    {
        $this->session_manager = $session_manager;
    }
    
    public function start(CookiePool $cookie_pool) :SessionInterface
    {
        if ( ! isset($this->session)) {
            $this->session = $this->session_manager->start($cookie_pool);
        }
        
        return $this->session;
    }
    
    public function save(SessionInterface $session) :void
    {
        $this->session_manager->save($session);
    }
    
    public function toCookie(ImmutableSessionInterface $session) :SessionCookie
    {
        return $this->session_manager->toCookie($session);
    }
    
}