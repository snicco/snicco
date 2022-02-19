<?php

declare(strict_types=1);

namespace Snicco\Component\WPObjectCachePsr16;

use Snicco\Component\BetterWPAPI\BetterWPAPI;

use function wp_cache_flush;
use function wp_cache_get_multiple;

/**
 * @psalm-internal Snicco\Component\WPObjectCachePsr16
 *
 * @interal
 */
final class WPCacheAPI extends BetterWPAPI
{

    /**
     * @param string[] $keys
     *
     * @return array<string,mixed>
     */
    public function cacheGetMultiple(array $keys, string $group = '', bool $force_reload = false): array
    {
        /** @var array<string,mixed> $res */
        $res = wp_cache_get_multiple($keys, $group, $force_reload);
        return $res;
    }

    public function cacheFlush(): bool
    {
        return wp_cache_flush();
    }

}