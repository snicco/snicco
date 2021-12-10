<?php

declare(strict_types=1);

namespace Snicco\Session\Drivers;

use DateTimeImmutable;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Exceptions\BadSessionID;
use Snicco\Session\Exceptions\CantDestroySession;
use Snicco\Session\Exceptions\CantReadSessionContent;
use Snicco\Session\ValueObjects\SerializedSessionData;
use Snicco\Session\Exceptions\CantWriteSessionContent;

use function explode;
use function wp_cache_set;
use function wp_cache_get;
use function base64_encode;
use function base64_decode;
use function wp_cache_delete;

/**
 * @api
 * @todo Cleanup uncommented code
 */
final class WPObjectCacheDriver implements SessionDriver
{
    
    private const LAST_ACTIVITY_DELIMITER = '|last_active|';
    
    /**
     * @var string
     */
    private $cache_group;
    
    /**
     * @var int
     */
    private $idle_timeout_in_seconds;
    
    public function __construct(string $cache_group, int $idle_timeout_in_seconds)
    {
        $this->cache_group = $cache_group;
        $this->idle_timeout_in_seconds = $idle_timeout_in_seconds;
    }
    
    public function destroy(array $session_ids) :void
    {
        foreach ($session_ids as $session_id) {
            $success = wp_cache_delete($session_id, $this->cache_group);
            if ($success === false) {
                throw CantDestroySession::forId($session_id, 'wp_object_cache');
            }
        }
    }
    
    public function gc(int $seconds_without_activity) :void
    {
        // Garbage collection should be handled automatically by the persistent object cache plugin.
    }
    
    public function read(string $session_id) :SerializedSessionData
    {
        $cache_content = explode(self::LAST_ACTIVITY_DELIMITER, $this->_read($session_id));
        
        return SerializedSessionData::fromSerializedString(
            base64_decode($cache_content[0]),
            (int) $cache_content[1]
        );
    }
    
    public function touch(string $session_id, DateTimeImmutable $now) :void
    {
        $cache_content = explode(self::LAST_ACTIVITY_DELIMITER, $this->_read($session_id));
        
        $data = $cache_content[0].self::LAST_ACTIVITY_DELIMITER.$now->getTimestamp();
        
        $this->_write($session_id, $data);
    }
    
    public function write(string $session_id, SerializedSessionData $data) :void
    {
        $data = base64_encode($data->asString()).
                self::LAST_ACTIVITY_DELIMITER.$data->lastActivity()->getTimestamp();
        
        $this->_write($session_id, $data);
    }
    
    private function _read(string $cache_key)
    {
        $payload = wp_cache_get($cache_key, $this->cache_group, false, $found);
        
        if ( ! $found) {
            throw BadSessionID::forId($cache_key, 'wp_object_cache');
        }
        
        if ($payload === false) {
            throw CantReadSessionContent::forID($cache_key, 'wp_object_cache');
        }
        return $payload;
    }
    
    private function _write(string $cache_key, string $data) :void
    {
        $success = wp_cache_set(
            $cache_key,
            $data,
            $this->cache_group,
            $this->idle_timeout_in_seconds
        );
        
        if ($success === false) {
            throw CantWriteSessionContent::forId($cache_key, 'wp_object_cache');
        }
    }
    
}