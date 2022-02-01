<?php

declare(strict_types=1);

namespace Snicco\Component\Session\SessionManager;

use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\EventDispatcher\NullSessionDispatcher;
use Snicco\Component\Session\EventDispatcher\SessionEventDispatcher;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\ReadWriteSession;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\SessionConfig;
use Snicco\Component\Session\ValueObject\SessionCookie;
use Snicco\Component\Session\ValueObject\SessionId;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;

use function is_null;

/**
 * This session manager will always return a new session object when start is being called.
 *
 * @api
 */
final class FactorySessionManager implements SessionManager
{

    private SessionConfig $config;
    private SessionDriver $driver;
    private Clock $clock;
    private SessionEventDispatcher $event_dispatcher;

    public function __construct(
        SessionConfig $config,
        SessionDriver $driver,
        ?Clock $clock = null,
        ?SessionEventDispatcher $event_dispatcher = null
    ) {
        $this->config = $config;
        $this->driver = $driver;
        $this->clock = $clock ?: new SystemClock();
        $this->event_dispatcher = $event_dispatcher ?: new NullSessionDispatcher();
    }

    public function start(CookiePool $cookie_pool): Session
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

    private function parseSessionId(CookiePool $cookie_pool): SessionId
    {
        $id = $cookie_pool->get($this->config->cookieName()) ?? '';
        return SessionId::fromCookieId($id);
    }

    private function loadSessionFromDriver(SessionId $id): ReadWriteSession
    {
        try {
            $data = $this->driver->read($id->asHash());
            $session = new ReadWriteSession($id, $data->asArray(), $data->lastActivity());
        } catch (BadSessionID $e) {
            $session = new ReadWriteSession(
                SessionId::createFresh(),
                [],
                $this->clock->currentTime()
            );
        }
        return $session;
    }

    private function isIdle(Session $session): bool
    {
        $inactivity = $this->clock->currentTimestamp() - $session->lastActivity();

        return $inactivity > $this->config->idleTimeoutInSec();
    }

    private function needsRotation(Session $session): bool
    {
        $period_since_last_rotation = $this->clock->currentTimestamp() - $session->lastRotation();

        return $period_since_last_rotation > $this->config->rotationInterval();
    }

    private function isExpired(Session $session): bool
    {
        $abs_lifetime = $this->config->absoluteLifetimeInSec();
        if (is_null($abs_lifetime)) {
            return false;
        }

        $lifetime = $this->clock->currentTimestamp() - $session->createdAt();

        return $lifetime > $this->config->absoluteLifetimeInSec();
    }

    public function save(Session $session): void
    {
        $session->saveUsing($this->driver, $this->clock->currentTime());

        $this->event_dispatcher->dispatchAll($session->releaseEvents());

        if ($this->config->gcLottery()->wins()) {
            $this->destroyInactiveSessions();
        }
    }

    private function destroyInactiveSessions(): void
    {
        $this->driver->gc($this->config->idleTimeoutInSec());
    }

    public function toCookie(ImmutableSession $session): SessionCookie
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

}