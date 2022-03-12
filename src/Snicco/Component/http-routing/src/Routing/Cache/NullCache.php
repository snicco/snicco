<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Cache;

final class NullCache implements RouteCache
{
    public function get(callable $loader): array
    {
        return $loader();
    }
}
