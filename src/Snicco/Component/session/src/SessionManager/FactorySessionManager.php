<?php

declare(strict_types=1);

namespace Snicco\Component\Session\SessionManager;

use RuntimeException;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\EventDispatcher\NullSessionDispatcher;
use Snicco\Component\Session\EventDispatcher\SessionEventDispatcher;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\ReadWriteSession;
use Snicco\Component\Session\Serializer\Serializer;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\SessionConfig;
use Snicco\Component\Session\ValueObject\SessionCookie;
use Snicco\Component\Session\ValueObject\SessionId;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Throwable;

use function hash_equals;

/**
 * This session manager will always return a new session object when start is being called.
 */
final class FactorySessionManager implements SessionManager
{
    private SessionConfig $config;

    private SessionDriver $driver;

    private Clock $clock;

    private SessionEventDispatcher $event_dispatcher;

    private Serializer $serializer;

    public function __construct(
        SessionConfig $config,
        SessionDriver $driver,
        Serializer $serializer,
        ?Clock $clock = null,
        ?SessionEventDispatcher $event_dispatcher = null
    ) {
        $this->config = $config;
        $this->driver = $driver;
        $this->serializer = $serializer;
        $this->clock = $clock ?: SystemClock::fromUTC();
        $this->event_dispatcher = $event_dispatcher ?: new NullSessionDispatcher();
    }

    public function start(CookiePool $cookie_pool): Session
    {
        $id = $this->parseSessionId($cookie_pool);

        $session = $this->loadSessionFromDriver($id);

        if ($this->isIdle($session) || $this->isExpired($session)) {
            $session->invalidate();
        } elseif ($this->needsRotation($session)) {
            $session->rotate();
        }

        return $session;
    }

    public function save(Session $session): void
    {
        $hashed_validator = $this->hash($session->id()->validator());

        $session->saveUsing(
            $this->driver,
            $this->serializer,
            $hashed_validator,
            $this->clock->currentTimestamp()
        );

        $this->event_dispatcher->dispatchAll($session->releaseEvents());
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

    public function gc(): void
    {
        if ($this->config->gcLottery()->wins()) {
            $this->driver->gc($this->config->idleTimeoutInSec());
        }
    }

    private function parseSessionId(CookiePool $cookie_pool): SessionId
    {
        $id = $cookie_pool->get($this->config->cookieName()) ?? '';
        return SessionId::fromCookieId($id);
    }

    private function loadSessionFromDriver(SessionId $id): ReadWriteSession
    {
        try {
            $serialized_session = $this->driver->read($id->selector());

            $stored_validator = $serialized_session->hashedValidator();
            $provided_validator = $this->hash($id->validator());

            // Do we have a timing-based side-channel attack?
            if (! hash_equals($stored_validator, $provided_validator)) {
                try {
                    $this->driver->destroy([$id->selector()]);
                } // @codeCoverageIgnoreStart
                catch (Throwable $e) {
                    // Don't handle this exception. Its more important to let the developer know about a possible attack.
                }
                // @codeCoverageIgnoreEnd

                throw new RuntimeException(
                    "Possible session brute force attack.\nHashed validator did not match for session selector [{$id->selector()}]."
                );
            }

            $data = $this->serializer->deserialize($serialized_session->data());

            return new ReadWriteSession($id, $data, $serialized_session->lastActivity());
        } catch (BadSessionID $e) {
            $session = ReadWriteSession::createEmpty($this->clock->currentTimestamp());
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
        if (null === $abs_lifetime) {
            return false;
        }

        $lifetime = $this->clock->currentTimestamp() - $session->createdAt();

        return $lifetime > $this->config->absoluteLifetimeInSec();
    }

    private function hash(string $verifier): string
    {
        $hash = hash('sha256', $verifier);
        if (false === $hash) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not hash session id.');
            // @codeCoverageIgnoreEnd
        }
        return $hash;
    }
}
