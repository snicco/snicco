<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\helpers;

use Snicco\Component\Kernel\Cache\BootstrapCache;

/**
 * @interal
 *
 * @psalm-internal Snicco
 */
final class FixedBootstrapCache implements BootstrapCache
{
    private array $config;

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getOr(string $cache_key, callable $loader): array
    {
        return $this->config;
    }
}
