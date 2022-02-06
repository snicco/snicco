<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use LogicException;

use function in_array;
use function is_int;
use function ucwords;

/**
 * @api
 * @psalm-immutable
 */
final class Cookie
{
    /**
     * @var array{
     *     domain: null|string,
     *     hostonly: bool,
     *     path: string,
     *     expires: null|positive-int,
     *     httponly: bool,
     *     samesite: 'Lax'|'Strict'|'None',
     *     secure: bool
     * }
     */
    public array $properties = [
        'domain' => null,
        'hostonly' => true,
        'path' => '/',
        'expires' => null,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    public string $name;
    public string $value;

    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function withJsAccess(): Cookie
    {
        $cookie = clone $this;
        $cookie->properties['httponly'] = false;
        return $cookie;
    }

    public function withOnlyHttpAccess(): Cookie
    {
        $cookie = clone $this;
        $cookie->properties['httponly'] = true;
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
     * @psalm-param  'Lax'|'Strict'|'None'|'None; Secure' $same_site
     */
    public function withSameSite(string $same_site): Cookie
    {
        $same_site = ucwords($same_site);

        if ($same_site === 'None; Secure') {
            $same_site = 'None';
        }

        if (!in_array($same_site, ['Lax', 'Strict', 'None'])) {
            throw new LogicException(
                "The value [$same_site] is not supported for the SameSite cookie."
            );
        }

        $cookie = clone $this;

        $cookie->properties['samesite'] = $same_site;

        if ($same_site === 'None') {
            $cookie->properties['secure'] = true;
        }

        return $cookie;
    }

    /**
     * @param positive-int|DateTimeImmutable $timestamp
     */
    public function withExpiryTimestamp($timestamp): Cookie
    {
        if (!is_int($timestamp) && !$timestamp instanceof DateTimeImmutable) {
            throw new InvalidArgumentException(
                '$timestamp must be an integer or DataTimeInterface'
            );
        }

        $timestamp = $timestamp instanceof DateTimeInterface
            ? $timestamp->getTimestamp()
            : $timestamp;

        $cookie = clone $this;

        $cookie->properties['expires'] = $timestamp;

        return $cookie;
    }

}