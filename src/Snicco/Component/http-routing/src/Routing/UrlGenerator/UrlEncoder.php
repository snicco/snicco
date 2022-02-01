<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

/**
 * @interal
 */
interface UrlEncoder
{

    /**
     * @param array<string,string|int> $query
     *
     * @return string
     */
    public function encodeQuery(array $query): string;

    public function encodePath(string $path): string;

    /**
     * @param string $fragment the "#" does not have to be passed.
     *
     * @return string
     */
    public function encodeFragment(string $fragment): string;

}