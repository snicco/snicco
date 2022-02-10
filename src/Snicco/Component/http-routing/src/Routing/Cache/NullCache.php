<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\Cache;


class NullCache implements RouteCacheInterface
{
    public function get(): ?array
    {
        return null;
    }
}