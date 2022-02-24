<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing;

use Snicco\Component\Kernel\Configuration\ConfigCache;

final class FixedConfigCache implements ConfigCache
{

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $key, callable $loader): array
    {
        return $this->config;
    }
}