<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Admin;

use Snicco\Component\HttpRouting\Routing\UrlPath;
use Webmozart\Assert\Assert;

final class AdminAreaPrefix
{
    private string $prefix;

    private function __construct(string $prefix)
    {
        Assert::startsWith($prefix, '/');
        Assert::notEndsWith($prefix, '/');
        $this->prefix = $prefix;
    }

    public function __toString(): string
    {
        return $this->prefix;
    }

    public static function fromString(string $prefix): AdminAreaPrefix
    {
        return new self('/' . trim($prefix, '/'));
    }

    public function asString(): string
    {
        return $this->prefix;
    }

    public function appendPath(string $path): string
    {
        return (string) UrlPath::fromString($this->prefix)->append($path);
    }
}
