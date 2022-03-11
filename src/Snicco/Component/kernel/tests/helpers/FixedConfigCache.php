<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\helpers;

use Snicco\Component\Kernel\Configuration\ConfigCache;

final class FixedConfigCache implements ConfigCache
{
    private array $config;

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config)
    {
        if (! isset($config['app'])) {
            $config['app'] = [];
        }
        if (! isset($config['app']['bootstrappers'])) {
            /** @psalm-suppress MixedArrayAssignment */
            $config['app']['bootstrappers'] = [];
        }
        if (! isset($config['bundles'])) {
            $config['bundles'] = [];
        }
        $this->config = $config;
    }

    public function get(string $key, callable $loader): array
    {
        return $this->config;
    }
}
