<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\Cache;


use Closure;

class NullCache implements RouteCacheInterface
{

    public function get(string $key, Closure $loader): ?array
    {
        return $loader();
    }
}