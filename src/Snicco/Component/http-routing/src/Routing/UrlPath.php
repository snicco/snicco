<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing;

use Snicco\Component\StrArr\Str;
use Webmozart\Assert\Assert;

use function ltrim;
use function rtrim;

/**
 * @api
 */
final class UrlPath
{

    // without leading slash
    private string $path;

    private function __construct(string $path)
    {
        $this->path = $path;
    }

    public function withTrailingSlash(): UrlPath
    {
        $new = clone $this;
        $new->path = rtrim($this->path, '/') . '/';
        return $new;
    }

    public function withoutTrailingSlash(): UrlPath
    {
        $new = clone $this;
        $new->path = rtrim($this->path, '/');
        return $new;
    }

    /**
     * @param string|UrlPath $path
     */
    public function prepend($path): UrlPath
    {
        $path = is_string($path) ? UrlPath::fromString($path) : $path;

        return UrlPath::fromString(rtrim($path->asString(), '/') . $this->asString());
    }

    public static function fromString(string $path): UrlPath
    {
        return new UrlPath(UrlPath::sanitize($path));
    }

    private static function sanitize(string $path): string
    {
        if ('' === $path) {
            $path = '/';
        }

        if (Str::endsWith($path, '//')) {
            $path = rtrim($path, '/') . '/';
        }

        return ltrim($path, '/');
    }

    public function asString(): string
    {
        return '/' . $this->path;
    }

    /**
     * @param string|UrlPath $path
     */
    public function append($path): UrlPath
    {
        $path = is_string($path) ? UrlPath::fromString($path) : $path;

        return UrlPath::fromString($this->asString() . $path->asString());
    }

    public function equals(string $path): bool
    {
        Assert::stringNotEmpty($path);
        return $this->asString() === '/' . ltrim($path, '/');
    }

    public function contains(string $path): bool
    {
        $path = trim($path, '/');
        return Str::contains($this->asString(), $path);
    }

    /**
     * @param string|UrlPath $path
     */
    public function startsWith($path): bool
    {
        $path = $path instanceof UrlPath ? $path : UrlPath::fromString($path);
        return Str::startsWith($this->asString(), $path->asString());
    }

    public function __toString(): string
    {
        return $this->asString();
    }

}