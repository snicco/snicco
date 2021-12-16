<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

use Snicco\Session\ValueObjects\CookiePool;
use Snicco\Session\ValueObjects\SessionCookie;
use Snicco\Session\Exceptions\CantDestroySession;
use Snicco\Session\Exceptions\CantReadSessionContent;
use Snicco\Session\Exceptions\CantWriteSessionContent;

/**
 * @api
 */
interface SessionManagerInterface
{
    
    /**
     * @throws CantReadSessionContent
     */
    public function start(CookiePool $cookie_pool) :SessionInterface;
    
    /**
     * @return SessionCookie
     * A value object that provides valid parameters to use in {@see setcookie()}
     */
    public function toCookie(ImmutableSessionInterface $session) :SessionCookie;
    
    /**
     * @throws CantWriteSessionContent If the session can't be saved.
     * @throws CantDestroySession If garbage collection did not work.
     */
    public function save(SessionInterface $session) :void;
    
}