<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache;

use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use Cache\Taggable\TaggablePSR6PoolAdapter;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

final class CacheFactory
{
    /**
     * @param non-empty-string $group
     */
    public static function psr6(string $group): WPObjectCachePsr6
    {
        return new WPObjectCachePsr6($group);
    }

    /**
     * @param non-empty-string $group
     */
    public static function psr16(string $group): CacheInterface
    {
        return new SimpleCacheBridge(self::psr6($group));
    }

    public static function taggable(CacheItemPoolInterface $psr6): TaggableCacheItemPoolInterface
    {
        return TaggablePSR6PoolAdapter::makeTaggable($psr6);
    }
}
