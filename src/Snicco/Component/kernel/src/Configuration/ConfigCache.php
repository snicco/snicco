<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

interface ConfigCache
{
    /**
     * @param callable():array $loader
     */
    public function get(string $key, callable $loader): array;
}
