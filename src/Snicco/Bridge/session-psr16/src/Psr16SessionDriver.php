<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionPsr16;

use BadMethodCallException;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Exception;
use InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Psr\SimpleCache\CacheInterface;
use Snicco\Component\Session\Driver\UserSessionsDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\Exception\CouldNotDestroySessions;
use Snicco\Component\Session\Exception\CouldNotReadSessionContent;
use Snicco\Component\Session\Exception\CouldNotWriteSessionContent;
use Snicco\Component\Session\ValueObject\SerializedSession;
use Throwable;
use Traversable;

use function array_key_exists;
use function get_class;
use function is_array;
use function is_int;
use function is_null;
use function is_string;

final class Psr16SessionDriver implements UserSessionsDriver
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

        if (true !== $res) {
            throw CouldNotDestroySessions::forSessionIDs($selectors, get_class($this->cache));
        }
    }

    public function gc(int $seconds_without_activity): void
    {
        //
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

    public function destroyAll(): void
    {
        if (!$this->cache instanceof TaggableCacheItemPoolInterface) {
            throw new BadMethodCallException(__METHOD__);
        }
        if (!$this->cache->invalidateTags(['sniccowp_user_sessions'])) {
            throw new CouldNotDestroySessions('Could not invalidate tags.');
        }
    }

    public function destroyAllForUserId($user_id): void
    {
        if (!$this->cache instanceof TaggableCacheItemPoolInterface) {
            throw new BadMethodCallException(__METHOD__);
        }
        $user_id = (string)$user_id;
        if (!$this->cache->invalidateTags(["sniccowp_user_id_$user_id"])) {
            throw new CouldNotDestroySessions('Could not invalidate tags.');
        }
    }

    public function destroyAllForUserIdExcept(string $selector, $user_id): void
    {
        if (!$this->cache instanceof TaggableCacheItemPoolInterface) {
            throw new BadMethodCallException(__METHOD__);
        }
        $item = $this->read($selector);
        $user_id = (string)$user_id;
        if (!$this->cache->invalidateTags(["sniccowp_user_id_$user_id"])) {
            throw new CouldNotDestroySessions('Could not invalidate tags.');
        }
        $this->write($selector, $item);
    }

    public function getAllForUserId($user_id): iterable
    {
        if (!$this->cache instanceof TaggableCacheItemPoolInterface) {
            throw new BadMethodCallException(__METHOD__);
        }

        $user_id = (string)$user_id;
        $item = $this->cache->getItem('user_id_' . $user_id . '_all_sessions');

        if (!$item->isHit()) {
            return [];
        }
        $list = (array)$item->get();

        /** @var Traversable<string,CacheItemInterface> $items */
        $items = $this->cache->getItems($list);

        $sessions = [];

        foreach ($items as $selector => $item) {
            if ($item->isHit()) {
                $sessions[$selector] = $this->read($selector);
            }
        }
        return $sessions;
    }

    /**
     * @return array{hashed_validator:string, data:string, user_id: int|string|null, last_activity:int}
     */
    private function readParts(string $session_id): array
    {
        try {
            $val = $this->cache->get($session_id);
        } catch (Exception $e) {
            throw CouldNotReadSessionContent::forID($session_id, self::class);
        }

        if (null === $val) {
            throw BadSessionID::forSelector($session_id, get_class($this->cache));
        }

        if (!is_array($val)) {
            throw new CouldNotReadSessionContent("Session content for id [$session_id] is not an array.");
        }

        if (!isset($val['last_activity']) || !is_int($val['last_activity'])) {
            throw new InvalidArgumentException(
                "Cache corrupted. [last_activity] is not an integer for selector [$session_id]."
            );
        }

        if (!isset($val['data']) || !is_string($val['data'])) {
            throw new InvalidArgumentException(
                "Cache corrupted. [data] is not a string for selector [$session_id]."
            );
        }

        if (!isset($val['hashed_validator']) || !is_string($val['hashed_validator'])) {
            throw new InvalidArgumentException(
                "Cache corrupted. [hashed_validator] is not a string for selector [$session_id]."
            );
        }

        if (
            !array_key_exists('user_id', $val)
            ||
            (!is_string($val['user_id']) && !is_int($val['user_id']) && !is_null($val['user_id']))
        ) {
            throw new InvalidArgumentException(
                "Cache corrupted. [user_id] is not a null,string or integer for selector [$session_id]."
            );
        }

        return $val;
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
        $val = [
            'selector' => $selector,
            'hashed_validator' => $hashed_validator,
            'data' => $data,
            'last_activity' => $last_activity,
            'user_id' => $user_id
        ];

        try {
            if ($this->cache instanceof TaggableCacheItemPoolInterface) {
                $user_id = (string)$user_id;
                $item = $this->cache->getItem($selector);
                $item->set($val)->expiresAfter($this->idle_timeout_in_seconds);
                $item->setTags(['sniccowp_user_sessions', "sniccowp_user_id_$user_id"]);
                $res1 = $this->cache->save($item);

                $item = $this->cache->getItem('user_id_' . $user_id . '_all_sessions');
                if ($item->isHit()) {
                    $list = (array)$item->get();
                } else {
                    $list = [];
                }
                $list[] = $selector;
                $item->set($list)->setTags(['sniccowp_user_sessions']);
                $res2 = $this->cache->save($item);
                $res = $res1 && $res2;
            } else {
                $res = $this->cache->set($selector, $val, $this->idle_timeout_in_seconds);
            }
        } catch (Throwable $e) {
            throw CouldNotWriteSessionContent::forId($selector, get_class($this->cache), $e);
        }

        if (true !== $res) {
            throw CouldNotWriteSessionContent::forId($selector, get_class($this->cache));
        }
    }
}