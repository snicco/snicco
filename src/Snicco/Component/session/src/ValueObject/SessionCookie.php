<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use Snicco\Component\Session\SessionManager\SessionManager;

use function time;

final class SessionCookie
{
    private string $cookie_name;

    private string $session_id;

    /**
     * @var 0|positive-int|null
     */
    private ?int $life_time;

    private bool $http_only;

    private bool $secure;

    private string $path;

    private ?string $domain;

    /**
     * @var 'Lax'|'None; Secure'|'Strict'
     */
    private string $same_site;

    /**
     * This class MUST only be constructed with {@see SessionManager::toCookie()}.
     *
     * @psalm-internal Snicco\Component\Session
     *
     * @param 'Lax'|'None; Secure'|'Strict' $same_site
     * @param 0|positive-int|null           $life_time
     */
    public function __construct(
        string $cookie_name,
        string $session_id,
        ?int $life_time = null,
        bool $http_only = true,
        bool $secure = true,
        string $path = '/',
        string $domain = null,
        string $same_site = 'Lax'
    ) {
        $this->cookie_name = $cookie_name;
        $this->session_id = $session_id;
        $this->life_time = $life_time;
        $this->http_only = $http_only;
        $this->secure = $secure;
        $this->path = $path;
        $this->domain = $domain;
        $this->same_site = $same_site;
    }

    public function value(): string
    {
        return $this->session_id;
    }

    public function name(): string
    {
        return $this->cookie_name;
    }

    /**
     * @return "Lax"|"None; Secure"|"Strict"
     */
    public function sameSite(): string
    {
        return $this->same_site;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function domain(): ?string
    {
        return $this->domain;
    }

    public function secureOnly(): bool
    {
        return $this->secure;
    }

    public function httpOnly(): bool
    {
        return $this->http_only;
    }

    /**
     * @return 0|positive-int
     */
    public function expiryTimestamp(): int
    {
        if (null === $this->life_time) {
            return 0;
        }

        return time() + $this->life_time;
    }

    public function lifetime(): ?int
    {
        return $this->life_time;
    }
}
