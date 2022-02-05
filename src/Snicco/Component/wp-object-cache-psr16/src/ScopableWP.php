<?php

declare(strict_types=1);

namespace Snicco\Component\WPObjectCachePsr16;

use function wp_cache_flush;
use function wp_cache_get_multiple;

final class ScopableWP extends \Snicco\Component\ScopableWP\ScopableWP
{

    /**
     * @param string[] $keys
     * @return array<string,mixed>
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function cacheGetMultiple(array $keys, string $group = '', bool $force_reload = false): array
    {
        return wp_cache_get_multiple($keys, $group, $force_reload);
    }

    public function cacheFlush(): bool
    {
        return wp_cache_flush();
    }

}