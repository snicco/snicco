<?php

declare(strict_types=1);

namespace Snicco\Component\Session\SessionManager;

use Snicco\Component\Session\Session;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\SessionCookie;
use Snicco\Component\Session\Exception\CantDestroySession;
use Snicco\Component\Session\Exception\CantReadSessionContent;
use Snicco\Component\Session\Exception\CantWriteSessionContent;

/**
 * @api
 */
interface SessionManager
{
    
    /**
     * @throws CantReadSessionContent
     */
    public function start(CookiePool $cookie_pool) :Session;
    
    /**
     * @return SessionCookie
     * A value object that provides valid parameters to use in {@see setcookie()}
     */
    public function toCookie(ImmutableSession $session) :SessionCookie;
    
    /**
     * @throws CantWriteSessionContent If the session can't be saved.
     * @throws CantDestroySession If garbage collection did not work.
     */
    public function save(Session $session) :void;
    
}