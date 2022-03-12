<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Snicco\Component\BetterWPCache\Exception\Psr6InvalidArgumentException;

use function array_keys;
use function get_class;
use function gettype;
use function is_string;
use function preg_match;
use function serialize;
use function sprintf;
use function time;
use function unserialize;

final class WPObjectCachePsr6 implements CacheItemPoolInterface
{
    private WPCacheAPI $wp_object_cache;

    private string $wp_cache_group;

    /**
     * @var array<non-empty-string,array{item:WPCacheItem, expiration: ?int}>
     */
    private array $deferred_items = [];

    /**
     * @param non-empty-string $group
     *
     * @internal
     */
    public function __construct(string $group, WPCacheAPI $wp_object_cache = null)
    {
        $this->wp_cache_group = $group;
        $this->wp_object_cache = $wp_object_cache ?: new WPCacheAPI();
    }

    /**
     * Make sure to commit before we destruct.
     */
    public function __destruct()
    {
        $this->commit();
    }

    public function getItem($key): WPCacheItem
    {
        $this->validateKey($key);

        return $this->internalGet($key);
    }

    public function getItems(array $keys = []): array
    {
        if ([] === $keys) {
            return [];
        }

        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        /** @var array<non-empty-string,false|string> $fetched */
        $fetched = $this->wp_object_cache->cacheGetMultiple($keys, $this->wp_cache_group);

        $items = [];

        foreach ($fetched as $key => $value) {
            /**
             * @psalm-suppress RedundantCastGivenDocblockType
             *
             * PHP will cast (valid) numeric string keys like "0" to (int) 0.
             */
            $key = (string) $key;
            $items[$key] = $this->instantiateItem($key, $value);
        }

        return $items;
    }

    public function hasItem($key): bool
    {
        $this->validateKey($key);

        return $this->internalHas($key);
    }

    public function clear(): bool
    {
        $this->deferred_items = [];

        return $this->wp_object_cache->cacheFlush();
    }

    public function deleteItem($key): bool
    {
        $this->validateKey($key);

        return $this->internalDelete($key);
    }

    public function deleteItems(array $keys): bool
    {
        $deleted = true;
        foreach ($keys as $key) {
            $this->validateKey($key);
            if (! $this->internalDelete($key)) {
                $deleted = false;
            }
        }

        return $deleted;
    }

    public function save(CacheItemInterface $item): bool
    {
        $this->validateCacheItem($item);
        $value = serialize($item->get());
        $key = $item->getKey();

        $expiration = $item->expirationTimestamp();

        if (null === $expiration) {
            // (int) 0 means cache forever for the WPObjectCache
            $wp_cache_ttl = 0;
        } elseif ($expiration <= time()) {
            return $this->internalDelete($key);
        } else {
            $wp_cache_ttl = $expiration - time();
        }

        return $this->wp_object_cache->cacheSet($key, $value, $this->wp_cache_group, $wp_cache_ttl);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->validateCacheItem($item);
        $this->deferred_items[$item->getKey()] = [
            'item' => $item,
            'expiration' => $item->expirationTimestamp(),
        ];

        return true;
    }

    public function commit(): bool
    {
        $saved = true;
        foreach (array_keys($this->deferred_items) as $key) {
            /**
             * @psalm-suppress RedundantCastGivenDocblockType
             *
             * PHP will cast (valid) numeric string keys like "0" to (int) 0.
             */
            $item = $this->internalGetDeferred((string) $key);
            if ($item && ! $this->save($item)) {
                $saved = false;
            }
        }

        $this->deferred_items = [];

        return $saved;
    }

    /**
     * @psalm-assert non-empty-string $key
     * @psalm-pure
     *
     * @param mixed $key
     */
    private function validateKey($key): void
    {
        if (! is_string($key)) {
            throw new Psr6InvalidArgumentException(sprintf('Cache key must be string, "%s" given', gettype($key)));
        }

        if ('' === $key) {
            throw new Psr6InvalidArgumentException('Cache key cannot be an empty string');
        }

        if (preg_match('#[\{\}\(\)/\\\@\:]#', $key)) {
            throw new Psr6InvalidArgumentException(
                sprintf(
                    'Invalid key: "%s". The key contains one or more characters reserved for future extension: {}()/\@:',
                    $key
                )
            );
        }
    }

    /**
     * @param non-empty-string $key
     */
    private function internalDelete(string $key): bool
    {
        unset($this->deferred_items[$key]);

        $deleted = $this->wp_object_cache->cacheDelete($key, $this->wp_cache_group);
        // Deleting a value that doesn't exist should return true in the psr-interface.
        // The wp object cache will return false for deleting missing keys.
        if (! $deleted && ! $this->internalHas($key)) {
            $deleted = true;
        }

        return $deleted;
    }

    /**
     * @param non-empty-string $key
     */
    private function internalHas(string $key): bool
    {
        return $this->internalGet($key)
            ->isHit();
    }

    /**
     * @psalm-assert WPCacheItem $item
     */
    private function validateCacheItem(CacheItemInterface $item): void
    {
        if (! $item instanceof WPCacheItem) {
            throw new Psr6InvalidArgumentException(
                sprintf(
                    "Cache items are not transferable between pools. Item MUST implement WPCacheItem.\nGot [%s]",
                    get_class($item)
                )
            );
        }
    }

    /**
     * @param non-empty-string $key
     */
    private function internalGet(string $key): WPCacheItem
    {
        if (($item = $this->internalGetDeferred($key)) !== null) {
            return $item;
        }

        /** @var mixed $serialized_value */
        $serialized_value = $this->wp_object_cache->cacheGet(
            $key,
            $this->wp_cache_group,
            // It's important to force a reload here. Otherwise $cache_item->isHit() will not conform to the interface.
            true,
            $found
        );

        if (! $found) {
            return WPCacheItem::miss($key);
        }

        return $this->instantiateItem($key, $serialized_value);
    }

    /**
     * @param non-empty-string $key
     * @param mixed            $serialized_value
     */
    private function instantiateItem(string $key, $serialized_value): WPCacheItem
    {
        if (! is_string($serialized_value)) {
            return WPCacheItem::miss($key);
        }

        /** @var mixed $value */
        $value = unserialize($serialized_value);
        if (false === $value && 'b:0;' !== $serialized_value) {
            return WPCacheItem::miss($key);
        }

        return new WPCacheItem($key, $value, true);
    }

    /**
     * @param non-empty-string $key
     */
    private function internalGetDeferred(string $key): ?WPCacheItem
    {
        if (! isset($this->deferred_items[$key])) {
            return null;
        }

        $deferred = $this->deferred_items[$key];

        // Deferred items values must not be changed once in the queue.
        $item = clone $deferred['item'];
        $expiration = $deferred['expiration'];

        if (null === $expiration) {
            return $item;
        }

        if ($expiration <= time()) {
            unset($this->deferred_items[$key]);

            return null;
        }

        return $item;
    }
}
