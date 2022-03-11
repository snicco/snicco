<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Driver;

use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\Exception\CouldNotDestroySessions;
use Snicco\Component\Session\ValueObject\SerializedSession;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;

final class InMemoryDriver implements UserSessionsDriver
{
    /**
     * @var array<string,array{data:string, last_activity:positive-int, hashed_validator:string, user_id: int|string|null}>
     */
    private array $storage = [];

    private Clock $clock;

    private bool $fail_gc;

    public function __construct(Clock $clock = null, bool $fail_gc = false)
    {
        $this->clock = $clock ?? SystemClock::fromUTC();
        $this->fail_gc = $fail_gc;
    }

    public function destroy(array $selectors): void
    {
        foreach ($selectors as $session_id) {
            if (isset($this->storage[$session_id])) {
                unset($this->storage[$session_id]);
            }
        }
    }

    public function gc(int $seconds_without_activity): void
    {
        if ($this->fail_gc) {
            throw new CouldNotDestroySessions('InMemory driver force-failed garbage collection.');
        }

        $expiration = $this->calculateExpiration($seconds_without_activity);

        foreach ($this->storage as $sessionId => $session) {
            if ($session['last_activity'] <= $expiration) {
                unset($this->storage[$sessionId]);
            }
        }
    }

    public function read(string $selector): SerializedSession
    {
        if (! isset($this->storage[$selector])) {
            throw BadSessionID::forSelector($selector, 'array');
        }

        return SerializedSession::fromString(
            $this->storage[$selector]['data'],
            $this->storage[$selector]['hashed_validator'],
            $this->storage[$selector]['last_activity'],
            $this->storage[$selector]['user_id'],
        );
    }

    public function touch(string $selector, int $current_timestamp): void
    {
        if (! isset($this->storage[$selector])) {
            throw BadSessionID::forSelector($selector, self::class);
        }

        $this->storage[$selector]['last_activity'] = $current_timestamp;
    }

    public function write(string $selector, SerializedSession $session): void
    {
        $this->storage[$selector] = [
            'data' => $session->data(),
            'last_activity' => $session->lastActivity(),
            'hashed_validator' => $session->hashedValidator(),
            'user_id' => $session->userId(),
        ];
    }

    public function destroyAll(): void
    {
        $this->storage = [];
    }

    public function destroyAllForUserId($user_id): void
    {
        foreach ($this->storage as $selector => $data) {
            if ($data['user_id'] === $user_id) {
                unset($this->storage[$selector]);
            }
        }
    }

    public function destroyAllForUserIdExcept(string $selector, $user_id): void
    {
        foreach ($this->storage as $s => $data) {
            if ($data['user_id'] !== $user_id) {
                continue;
            }

            if ($s === $selector) {
                continue;
            }

            unset($this->storage[$s]);
        }
    }

    public function getAllForUserId($user_id): iterable
    {
        $return = [];

        foreach ($this->storage as $selector => $data) {
            if ($data['user_id'] === $user_id) {
                $return[$selector] = $this->read($selector);
            }
        }

        return $return;
    }

    public function all(): array
    {
        return $this->storage;
    }

    private function calculateExpiration(int $seconds): int
    {
        return $this->clock->currentTimestamp() - $seconds;
    }
}
