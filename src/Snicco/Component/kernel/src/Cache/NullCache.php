<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Cache;

/**
 * @interal
 *
 * @psalm-internal Snicco
 */
final class NullCache implements BootstrapCache
{
    public function getOr(string $cache_key, callable $loader, bool $force_reload = false): array
    {
        return $loader();
    }
}
