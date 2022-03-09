<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Cache;

interface RouteCache
{
    /**
     * @param callable():array{route_collection: array<string,string>, url_matcher: array, admin_menu: array<string>} $loader
     *
     * @return array{route_collection: array<string,string>, url_matcher: array, admin_menu: array<string>}
     */
    public function get(callable $loader): array;
}
