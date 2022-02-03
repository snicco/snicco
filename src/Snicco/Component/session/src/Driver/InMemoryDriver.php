<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Driver;

use DateTimeImmutable;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\ValueObject\SerializedSessionData;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;

/**
 * @api
 */
final class InMemoryDriver implements SessionDriver
{

    /**
     * @var array<string,array{data:string, last_activity:positive-int}>
     */
    private array $storage = [];
    private Clock $clock;

    public function __construct(Clock $clock = null)
    {
        $this->clock = $clock ?? new SystemClock();
    }

    public function destroy(array $session_ids): void
    {
        foreach ($session_ids as $session_id) {
            if (isset($this->storage[$session_id])) {
                unset($this->storage[$session_id]);
            }
        }
    }

    public function gc(int $seconds_without_activity): void
    {
        $expiration = $this->calculateExpiration($seconds_without_activity);

        foreach ($this->storage as $sessionId => $session) {
            if ($session['last_activity'] < $expiration) {
                unset($this->storage[$sessionId]);
            }
        }
    }

    private function calculateExpiration(int $seconds): int
    {
        return $this->clock->currentTimestamp() - $seconds;
    }

    public function read(string $session_id): SerializedSessionData
    {
        if (!isset($this->storage[$session_id])) {
            throw BadSessionID::forID($session_id, 'array');
        }
        return SerializedSessionData::fromSerializedString(
            $this->storage[$session_id]['data'],
            $this->storage[$session_id]['last_activity'],
        );
    }

    public function touch(string $session_id, DateTimeImmutable $now): void
    {
        if (!isset($this->storage[$session_id])) {
            throw BadSessionID::forId($session_id, 'array');
        }

        $this->storage[$session_id]['last_activity'] = $now->getTimestamp();
    }

    public function write(string $session_id, SerializedSessionData $data): void
    {
        $this->storage[$session_id] = [
            'data' => $data->asString(),
            'last_activity' => $data->lastActivity()->getTimestamp(),
        ];
    }

    public function all(): array
    {
        return $this->storage;
    }

}