<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionPsr16;

use DateTimeImmutable;
use Exception;
use Psr\SimpleCache\CacheInterface;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\Exception\CantDestroySession;
use Snicco\Component\Session\Exception\CantReadSessionContent;
use Snicco\Component\Session\Exception\CantWriteSessionContent;
use Snicco\Component\Session\ValueObject\SerializedSessionData;
use Throwable;

use function base64_decode;
use function base64_encode;
use function count;
use function explode;
use function get_class;
use function is_string;
use function strval;

final class Psr16Driver implements SessionDriver
{

    private CacheInterface $cache;
    private int $idle_timeout_in_seconds;

    public function __construct(CacheInterface $cache, int $idle_timeout_in_seconds)
    {
        $this->cache = $cache;
        $this->idle_timeout_in_seconds = $idle_timeout_in_seconds;
    }

    public function read(string $session_id): SerializedSessionData
    {
        [$last_activity, $data] = $this->readParts($session_id);

        return SerializedSessionData::fromSerializedString(
            $data,
            $last_activity
        );
    }

    /**
     * @throws Throwable
     * @throws CantWriteSessionContent
     */
    public function write(string $session_id, SerializedSessionData $data): void
    {
        $this->writeParts(
            $session_id,
            $data->lastActivity()->getTimestamp(),
            $data->asString()
        );
    }

    /**
     * @throws Throwable
     * @throws CantWriteSessionContent
     */
    public function destroy(array $session_ids): void
    {
        try {
            $res = $this->cache->deleteMultiple($session_ids);
        } catch (Throwable $e) {
            throw CantDestroySession::forSessionIDs($session_ids, get_class($this->cache), $e);
        }

        if (true !== $res) {
            throw CantDestroySession::forSessionIDs($session_ids, get_class($this->cache));
        }
    }

    public function gc(int $seconds_without_activity): void
    {
        //
    }

    public function touch(string $session_id, DateTimeImmutable $now): void
    {
        $data = $this->readParts($session_id)[1];

        $this->writeParts($session_id, $now->getTimestamp(), $data);
    }

    /**
     * @return array{0:positive-int, 1:string}
     */
    private function readParts(string $session_id): array
    {
        try {
            $val = $this->cache->get($session_id);
        } catch (Exception $e) {
            throw CantReadSessionContent::forID($session_id, self::class);
        }

        if (null === $val) {
            throw BadSessionID::forId($session_id, get_class($this->cache));
        }

        if (!is_string($val)) {
            throw new CantReadSessionContent("Session content for id [$session_id] is not a string.");
        }

        $decoded = base64_decode($val, true);
        if (false === $decoded) {
            throw new CantReadSessionContent("Cant decode session contents for id [$session_id].\nContent: [$val].");
        }

        $parts = explode('|', $decoded);

        if (count($parts) !== 2) {
            throw new CantReadSessionContent(
                "Session content for id [$session_id] is corrupted.\nDecoded content: [$decoded]."
            );
        }

        return [(int)$parts[0], $parts[1]];
    }

    private function writeParts(string $session_id, int $last_activity, string $data): void
    {
        $val = strval($last_activity) . '|' . $data;

        try {
            $res = $this->cache->set($session_id, base64_encode($val), $this->idle_timeout_in_seconds);
        } catch (Exception $e) {
            throw CantWriteSessionContent::forId($session_id, get_class($this->cache), $e);
        }

        if (true !== $res) {
            throw CantWriteSessionContent::forId($session_id, get_class($this->cache));
        }
    }

}