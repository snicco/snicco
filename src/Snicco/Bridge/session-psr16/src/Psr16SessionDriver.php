<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionPsr16;

use Exception;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\Exception\CouldNotDestroySessions;
use Snicco\Component\Session\Exception\CouldNotReadSessionContent;
use Snicco\Component\Session\Exception\CouldNotWriteSessionContent;
use Snicco\Component\Session\ValueObject\SerializedSession;
use Throwable;

use function array_key_exists;
use function get_class;
use function is_array;
use function is_int;
use function is_string;

final class Psr16SessionDriver implements SessionDriver
{
    private CacheInterface $cache;

    private int $idle_timeout_in_seconds;

    public function __construct(CacheInterface $cache, int $idle_timeout_in_seconds)
    {
        $this->cache = $cache;
        $this->idle_timeout_in_seconds = $idle_timeout_in_seconds;
    }

    public function read(string $selector): SerializedSession
    {
        $session = $this->readParts($selector);

        return SerializedSession::fromString(
            $session['data'],
            $session['hashed_validator'],
            $session['last_activity'],
            $session['user_id']
        );
    }

    /**
     * @throws CouldNotWriteSessionContent
     */
    public function write(string $selector, SerializedSession $session): void
    {
        $this->writeParts(
            $selector,
            $session->hashedValidator(),
            $session->data(),
            $session->lastActivity(),
            $session->userId()
        );
    }

    /**
     * @throws CouldNotWriteSessionContent
     */
    public function destroy(array $selectors): void
    {
        try {
            $res = $this->cache->deleteMultiple($selectors);
        } catch (Throwable $e) {
            throw CouldNotDestroySessions::forSessionIDs($selectors, get_class($this->cache), $e);
        }

        if (! $res) {
            throw CouldNotDestroySessions::forSessionIDs($selectors, get_class($this->cache));
        }
    }

    public function gc(int $seconds_without_activity): void
    {
    }

    public function touch(string $selector, int $current_timestamp): void
    {
        $parts = $this->readParts($selector);
        $parts['last_activity'] = $current_timestamp;

        $this->writeParts(
            $selector,
            $parts['hashed_validator'],
            $parts['data'],
            $parts['last_activity'],
            $parts['user_id']
        );
    }

    /**
     * @return array{hashed_validator:string, data:string, user_id: int|string|null, last_activity:int}
     */
    private function readParts(string $session_id): array
    {
        try {
            $payload = $this->cache->get($session_id);
        } catch (Exception $e) {
            throw CouldNotReadSessionContent::forID($session_id, self::class);
        }

        if (null === $payload) {
            throw BadSessionID::forSelector($session_id, get_class($this->cache));
        }

        if (! is_array($payload)) {
            throw new CouldNotReadSessionContent(sprintf('Session content for id [%s] is not an array.', $session_id));
        }

        if (! isset($payload['last_activity']) || ! is_int($payload['last_activity'])) {
            throw new InvalidArgumentException(
                sprintf('Cache corrupted. [last_activity] is not an integer for selector [%s].', $session_id)
            );
        }

        if (! isset($payload['data']) || ! is_string($payload['data'])) {
            throw new InvalidArgumentException(
                sprintf('Cache corrupted. [data] is not a string for selector [%s].', $session_id)
            );
        }

        if (! isset($payload['hashed_validator']) || ! is_string($payload['hashed_validator'])) {
            throw new InvalidArgumentException(
                sprintf('Cache corrupted. [hashed_validator] is not a string for selector [%s].', $session_id)
            );
        }

        if (
            ! array_key_exists('user_id', $payload)
            ||
            (! is_string($payload['user_id']) && ! is_int($payload['user_id']) && null !== $payload['user_id'])
        ) {
            throw new InvalidArgumentException(
                sprintf('Cache corrupted. [user_id] is not a null,string or integer for selector [%s].', $session_id)
            );
        }

        return $payload;
    }

    /**
     * @param int|string|null $user_id
     */
    private function writeParts(
        string $selector,
        string $hashed_validator,
        string $data,
        int $last_activity,
        $user_id
    ): void {
        $payload = [
            'selector' => $selector,
            'hashed_validator' => $hashed_validator,
            'data' => $data,
            'last_activity' => $last_activity,
            'user_id' => $user_id,
        ];

        try {
            $res = $this->cache->set($selector, $payload, $this->idle_timeout_in_seconds);
        } catch (Throwable $e) {
            throw CouldNotWriteSessionContent::forId($selector, get_class($this->cache), $e);
        }

        if (! $res) {
            throw CouldNotWriteSessionContent::forId($selector, get_class($this->cache));
        }
    }
}
