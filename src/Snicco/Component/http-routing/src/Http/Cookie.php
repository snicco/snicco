<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use LogicException;

use function array_replace;
use function in_array;
use function is_int;
use function ucwords;

/**
 * @psalm-immutable
 */
final class Cookie
{
    /**
     * @interal
     *
     * @var array{
     *     domain: null|string,
     *     host_only: bool,
     *     path: string,
     *     expires: null|positive-int,
     *     http_only: bool,
     *     same_site: 'Lax'|'Strict'|'None',
     *     secure: bool
     * }
     */
    public array $properties;

    /**
     * @interal
     */
    public string $name;

    /**
     * @interal
     */
    public string $value;

    /**
     * @param null|array{
     *    domain?: null|string,
     *    host_only?: bool,
     *    path?: string,
     *    expires?: ?positive-int,
     *    secure?: bool,
     *    http_only?: bool,
     *    same_site: 'Lax'|'Strict'|'None'
     * } $properties
     */
    public function __construct(string $name, string $value, ?array $properties = null)
    {
        $this->name = $name;
        $this->value = $value;

        $this->properties = array_replace(
            [
                'domain' => null,
                'host_only' => true,
                'path' => '/',
                'expires' => null,
                'secure' => true,
                'http_only' => true,
                'same_site' => 'Lax',
            ],
            $properties ?: []
        );
    }

    public function withJsAccess(): Cookie
    {
        $cookie = clone $this;
        $cookie->properties['http_only'] = false;

        return $cookie;
    }

    public function withOnlyHttpAccess(): Cookie
    {
        $cookie = clone $this;
        $cookie->properties['http_only'] = true;

        return $cookie;
    }

    public function withUnsecureHttp(): Cookie
    {
        $cookie = clone $this;
        $cookie->properties['secure'] = false;

        return $cookie;
    }

    public function withPath(string $path): Cookie
    {
        $cookie = clone $this;
        $cookie->properties['path'] = $path;

        return $cookie;
    }

    public function withDomain(?string $domain): Cookie
    {
        $cookie = clone $this;

        $cookie->properties['domain'] = $domain;

        return $cookie;
    }

    /**
     * @param 'Lax'|'None'|'None; Secure'|'Strict' $same_site
     */
    public function withSameSite(string $same_site): Cookie
    {
        $same_site = ucwords($same_site);

        if ('None; Secure' === $same_site) {
            $same_site = 'None';
        }

        if (! in_array($same_site, ['Lax', 'Strict', 'None'], true)) {
            throw new LogicException(sprintf('The value [%s] is not supported for the SameSite cookie.', $same_site));
        }

        $cookie = clone $this;

        $cookie->properties['same_site'] = $same_site;

        if ('None' === $same_site) {
            $cookie->properties['secure'] = true;
        }

        return $cookie;
    }

    /**
     * @param 0|DateTimeImmutable|positive-int $timestamp
     */
    public function withExpiryTimestamp($timestamp): Cookie
    {
        if (! is_int($timestamp) && ! $timestamp instanceof DateTimeImmutable) {
            throw new InvalidArgumentException('$timestamp must be an integer or DataTimeInterface');
        }

        $timestamp = $timestamp instanceof DateTimeInterface
            ? $timestamp->getTimestamp()
            : $timestamp;

        $cookie = clone $this;

        $cookie->properties['expires'] = $timestamp;

        return $cookie;
    }
}
