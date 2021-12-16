<?php

declare(strict_types=1);

namespace Snicco\Session;

use Snicco\Session\Contracts\SessionClock;
use Snicco\Session\ValueObjects\SessionId;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Exceptions\BadSessionID;
use Snicco\Session\ValueObjects\CookiePool;
use Snicco\Session\ValueObjects\SessionConfig;
use Snicco\Session\ValueObjects\SessionCookie;
use Snicco\Session\Contracts\SessionInterface;
use Snicco\Session\Contracts\SessionEventDispatcher;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\Session\Contracts\ImmutableSessionInterface;

use function is_null;

/**
 * @interal
 */
final class SessionManager implements SessionManagerInterface
{
    
    /**
     * @var SessionConfig
     */
    private $config;
    
    /**
     * @var SessionDriver
     */
    private $driver;
    
    /**
     * @var SessionClock
     */
    private $clock;
    
    /**
     * @var SessionEventDispatcher
     */
    private $event_dispatcher;
    
    public function __construct(
        SessionConfig $config,
        SessionDriver $driver,
        SessionClock $clock,
        SessionEventDispatcher $event_dispatcher
    ) {
        $this->config = $config;
        $this->driver = $driver;
        $this->clock = $clock;
        $this->event_dispatcher = $event_dispatcher;
    }
    
    public function start(CookiePool $cookie_pool) :SessionInterface
    {
        $id = $this->parseSessionId($cookie_pool);
        
        $session = $this->loadSessionFromDriver($id);
        
        if ($this->isIdle($session)) {
            $session->invalidate();
        }
        
        if ($this->needsRotation($session)) {
            $session->rotate();
        }
        
        if ($this->isExpired($session)) {
            $session->invalidate();
        }
        
        return $session;
    }
    
    public function save(SessionInterface $session) :void
    {
        $session->saveUsing($this->driver, $this->clock->currentTime());
        
        $this->event_dispatcher->dispatchAll($session->releaseEvents());
        
        if ($this->config->gcLottery()->wins()) {
            $this->destroyInactiveSessions();
        }
    }
    
    public function toCookie(ImmutableSessionInterface $session) :SessionCookie
    {
        return new SessionCookie(
            $this->config->cookieName(),
            $session->id()->asString(),
            $this->config->absoluteLifetimeInSec(),
            $this->config->onlyHttp(),
            $this->config->onlySecure(),
            $this->config->cookiePath(),
            $this->config->cookieDomain(),
            $this->config->sameSite()
        );
    }
    
    private function isExpired(SessionInterface $session) :bool
    {
        $abs_lifetime = $this->config->absoluteLifetimeInSec();
        if (is_null($abs_lifetime)) {
            return false;
        }
        
        $lifetime = $this->clock->currentTimestamp() - $session->createdAt();
        
        return $lifetime > $this->config->absoluteLifetimeInSec();
    }
    
    private function isIdle(SessionInterface $session) :bool
    {
        $inactivity = $this->clock->currentTimestamp() - $session->lastActivity();
        
        return $inactivity > $this->config->idleTimeoutInSec();
    }
    
    private function needsRotation(SessionInterface $session) :bool
    {
        $period_since_last_rotation = $this->clock->currentTimestamp() - $session->lastRotation();
        
        return $period_since_last_rotation > $this->config->rotationInterval();
    }
    
    private function destroyInactiveSessions()
    {
        $this->driver->gc($this->config->idleTimeoutInSec());
    }
    
    private function parseSessionId(CookiePool $cookie_pool) :SessionId
    {
        $id = $cookie_pool->get($this->config->cookieName()) ?? '';
        return SessionId::fromCookieId($id);
    }
    
    private function loadSessionFromDriver(SessionId $id) :SessionInterface
    {
        try {
            $data = $this->driver->read($id->asHash());
            $session = new Session($id, $data->asArray(), $data->lastActivity());
        } catch (BadSessionID $e) {
            $session = new Session(
                SessionId::createFresh(),
                [],
                $this->clock->currentTime()
            );
        }
        return $session;
    }
    
}