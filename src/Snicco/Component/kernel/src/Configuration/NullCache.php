<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

final class NullCache implements ConfigCache
{
    public function get(string $key, callable $loader): array
    {
        return $loader();
    }
}
