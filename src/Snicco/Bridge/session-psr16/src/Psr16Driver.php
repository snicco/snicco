<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionPsr16;

use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\Exception\CantDestroySession;
use Snicco\Component\Session\ValueObject\SerializedSessionData;
use Snicco\Component\Session\Exception\CantWriteSessionContent;
use Psr\SimpleCache\InvalidArgumentException as InvalidPsrCacheKey;

use function count;
use function explode;
use function get_class;
use function base64_encode;
use function base64_decode;

final class Psr16Driver implements SessionDriver
{
    
    private CacheInterface $cache;
    private int            $idle_timeout_in_seconds;
    
    public function __construct(CacheInterface $cache, int $idle_timeout_in_seconds)
    {
        $this->cache = $cache;
        $this->idle_timeout_in_seconds = $idle_timeout_in_seconds;
    }
    
    public function read(string $session_id) :SerializedSessionData
    {
        [$last_activity, $data] = $this->readParts($session_id);
        
        return SerializedSessionData::fromSerializedString(
            $data,
            $last_activity
        );
    }
    
    public function write(string $session_id, SerializedSessionData $data) :void
    {
        $this->writeParts(
            $session_id,
            $data->lastActivity()->getTimestamp(),
            $data->asString()
        );
    }
    
    public function destroy(array $session_ids) :void
    {
        try {
            $this->cache->deleteMultiple($session_ids);
        } catch (InvalidPsrCacheKey $e) {
            throw CantDestroySession::forSessionIDs($session_ids, get_class($this->cache), $e);
        }
    }
    
    public function gc(int $seconds_without_activity) :void
    {
        //
    }
    
    public function touch(string $session_id, DateTimeImmutable $now) :void
    {
        [$last_activity, $data] = $this->readParts($session_id);
        
        $this->writeParts($session_id, $now->getTimestamp(), $data);
    }
    
    /**
     * @param  string  $session_id
     *
     * @return array<int,string>
     */
    private function readParts(string $session_id) :array
    {
        try {
            $val = $this->cache->get($session_id);
        } catch (InvalidPsrCacheKey $e) {
            throw BadSessionID::forId($session_id, get_class($this->cache));
        }
        
        if (null === $val) {
            throw BadSessionID::forId($session_id, get_class($this->cache));
        }
        
        $parts = explode('|', base64_decode($val));
        
        if (count($parts) !== 2) {
            throw BadSessionID::forId($session_id, get_class($this->cache));
        }
        
        return [(int) $parts[0], $parts[1]];
    }
    
    private function writeParts(string $session_id, int $last_activity, string $data) :void
    {
        $val = $last_activity.'|'.$data;
        
        try {
            $this->cache->set($session_id, base64_encode($val), $this->idle_timeout_in_seconds);
        } catch (InvalidPsrCacheKey $e) {
            throw CantWriteSessionContent::forId($session_id, get_class($this->cache), $e);
        }
    }
    
}