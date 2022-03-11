<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

interface UrlEncoder
{
    /**
     * @param array<string,int|string> $query
     */
    public function encodeQuery(array $query): string;

    public function encodePath(string $path): string;

    /**
     * @param string $fragment the "#" does not have to be passed
     */
    public function encodeFragment(string $fragment): string;
}
