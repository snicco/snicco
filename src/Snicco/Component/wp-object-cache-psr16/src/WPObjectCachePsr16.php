<?php

declare(strict_types=1);

namespace Snicco\Component\WPObjectCachePsr16;

use Traversable;
use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

use function is_int;
use function sprintf;
use function gettype;
use function is_array;
use function is_string;
use function serialize;
use function preg_match;
use function array_walk;
use function unserialize;
use function iterator_to_array;

final class WPObjectCachePsr16 implements CacheInterface
{
    
    private ScopableWP $wp;
    
    public function __construct(ScopableWP $wp)
    {
        $this->wp = $wp;
    }
    
    public function get($key, $default = null)
    {
        $this->validateKey($key);
        $content = $this->wp->cacheGet($key, '', true, $found);
        
        if (false === $content && false === $found) {
            return $default;
        }
        
        return unserialize($content);
    }
    
    public function set($key, $value, $ttl = null) :bool
    {
        $this->validateKey($key);
        
        // Setting an item with an expiration <=0 should not mean that its persisted forever
        // like in the messy interface that WordPress provides.
        // To guarantee that ttl conforms to the psr interface we have to delete the cache key.
        if (is_int($ttl) && $ttl <= 0) {
            $this->wp->cacheDelete($key);
            return true;
        }
        
        if (null === $ttl) {
            $expires = 0;
        }
        elseif (is_int($ttl)) {
            $expires = $ttl;
        }
        elseif ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable;
            $end = $now->add($ttl);
            $expires = $end->getTimestamp() - $now->getTimestamp();
        }
        else {
            throw new BadTtl(
                sprintf('$ttl must be null,integer or DateInterval. Got [%s]', gettype($ttl))
            );
        }
        
        return $this->wp->cacheSet($key, serialize($value), '', $expires);
    }
    
    public function delete($key) :bool
    {
        $this->validateKey($key);
        $res = $this->wp->cacheDelete($key);
        if (false === $res) {
            // Deleting a value that doesn't exist should return true in the psr-interface.
            // The wp object cache will return false for deleting missing keys.
            if ( ! $this->has($key)) {
                $res = true;
            }
        }
        return $res;
    }
    
    public function clear() :bool
    {
        return $this->wp->cacheFlush();
    }
    
    public function getMultiple($keys, $default = null) :array
    {
        if ( ! is_array($keys)) {
            if ( ! $keys instanceof Traversable) {
                throw new BadKey(
                    sprintf('$keys must be array or Traversable. Got [%s]', gettype($keys))
                );
            }
            $keys = iterator_to_array($keys, false);
        }
        
        array_walk($keys, [$this, 'validateKey']);
        
        $res = $this->wp->cacheGetMultiple($keys);
        
        $values = [];
        foreach ($res as $key => $value) {
            $values[$key] = (false === $value) ? $default : unserialize($value);
        }
        return $values;
    }
    
    /**
     * @note It's not possible for us to set multiple keys in one operation.
     *       There is NOTHING we can do on that front until WordPress core decides to add these
     *       methods as a requirement.
     */
    public function setMultiple($values, $ttl = null) :bool
    {
        if ( ! is_array($values)) {
            if ( ! $values instanceof Traversable) {
                throw new BadKey(
                    sprintf('$value must be array or Traversable. Got [%s]', gettype($values))
                );
            }
        }
        
        $iterator = [];
        
        // Don't set values in this loop because there might be invalid keys in a later iteration.
        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $key = (string) $key;
            }
            $this->validateKey($key);
            $iterator[$key] = $value;
        }
        
        $res = true;
        foreach ($iterator as $key => $item) {
            if (is_int($key)) {
                $key = (string) $key;
            }
            $res = $this->set($key, $item, $ttl);
        }
        return $res;
    }
    
    /**
     * @note It's not possible for us to delete multiple keys in one operation.
     *       There is NOTHING we can do on that front until WordPress core decides to add these
     *       methods as a requirement.
     */
    public function deleteMultiple($keys) :bool
    {
        if ( ! is_array($keys)) {
            if ( ! $keys instanceof Traversable) {
                throw new BadKey(
                    sprintf('$keys must be array or Traversable. Got [%s]', gettype($keys))
                );
            }
            $keys = iterator_to_array($keys, false);
        }
        
        $res = true;
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
        foreach ($keys as $key) {
            $res = $this->delete($key);
        }
        return $res;
    }
    
    public function has($key) :bool
    {
        $this->validateKey($key);
        $this->wp->cacheGet($key, '', true, $found);
        return true === $found;
    }
    
    /**
     * @param  string|array  $key
     *
     * @throws BadKey
     */
    private function validateKey($key) :void
    {
        if ( ! is_string($key)) {
            throw new BadKey(
                sprintf(
                    'Cache key must be string, "%s" given',
                    gettype($key)
                )
            );
        }
        if ( ! isset($key[0])) {
            throw new BadKey('Cache key cannot be an empty string');
        }
        if (preg_match('|[\{\}\(\)/\\\@\:]|', $key)) {
            throw new BadKey(
                sprintf(
                    'Invalid key: "%s". The key contains one or more characters reserved for future extension: {}()/\@:',
                    $key
                )
            );
        }
    }
    
}