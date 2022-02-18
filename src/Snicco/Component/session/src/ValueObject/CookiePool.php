<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

final class CookiePool
{

    /**
     * @var array<string,string>
     */
    private array $cookies;

    /**
     * @param array<string,string> $cookies
     */
    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public static function fromSuperGlobals(): CookiePool
    {
        return new CookiePool($_COOKIE);
    }

    /**
     * @interal
     * @psalm-internal Snicco\Component\Session
     */
    public function has(string $cookie_name): bool
    {
        return isset($this->cookies[$cookie_name]);
    }

    /**
     * @interal
     * @psalm-internal Snicco\Component\Session
     */
    public function get(string $cookie_name): ?string
    {
        return $this->cookies[$cookie_name] ?? null;
    }

}