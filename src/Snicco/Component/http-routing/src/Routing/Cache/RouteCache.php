<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\Cache;


interface RouteCache
{
    /**
     * @param callable():array{route_collection: array<string,string>, url_matcher: array} $loader
     * @return array{route_collection: array<string,string>, url_matcher: array}
     */
    public function get(callable $loader): array;
}