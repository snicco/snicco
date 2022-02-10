<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\Cache;

use Closure;

interface RouteCacheInterface
{
    public function get(string $key, Closure $loader): ?array;
}