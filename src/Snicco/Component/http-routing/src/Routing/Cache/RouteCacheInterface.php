<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\Cache;

interface RouteCacheInterface
{
    public function get(): ?array;
}