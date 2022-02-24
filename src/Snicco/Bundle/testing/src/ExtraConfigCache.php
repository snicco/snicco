<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing;


use Snicco\Component\Kernel\Configuration\ConfigCache;
use Snicco\Component\StrArr\Arr;

final class ExtraConfigCache implements ConfigCache
{

    private array $extra_config;

    public function __construct(array $extra_config)
    {
        $this->extra_config = $extra_config;
    }

    public function get(string $key, callable $loader): array
    {
        $loaded_config = $loader();
        return Arr::mergeRecursive($loaded_config, $this->extra_config);
    }
}