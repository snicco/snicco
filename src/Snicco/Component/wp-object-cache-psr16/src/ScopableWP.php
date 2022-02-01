<?php

declare(strict_types=1);

namespace Snicco\Component\WPObjectCachePsr16;

use function wp_cache_flush;
use function wp_cache_get_multiple;

final class ScopableWP extends \Snicco\Component\ScopableWP\ScopableWP
{

    /**
     * @param string[] $keys
     */
    public function cacheGetMultiple(
        array $keys,
        string $group = '',
        bool $force_reload_from_persistent_cache = false
    ): array {
        return wp_cache_get_multiple($keys, $group, $force_reload_from_persistent_cache);
    }

    public function cacheFlush(): bool
    {
        return wp_cache_flush();
    }

}