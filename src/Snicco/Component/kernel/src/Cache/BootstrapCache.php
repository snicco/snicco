<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Cache;

/**
 * @interal
 *
 * @psalm-internal Snicco
 */
interface BootstrapCache
{
    /**
     * @param non-empty-string $cache_key The cache key MUST be not be user-provided, and must be in the charset [a-zA-Z0-9_./]
     * @param callable():array $loader
     */
    public function getOr(string $cache_key, callable $loader, bool $force_reload = false): array;
}
