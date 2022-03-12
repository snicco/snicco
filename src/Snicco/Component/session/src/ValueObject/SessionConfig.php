<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use InvalidArgumentException;

use function array_replace;
use function implode;
use function in_array;
use function ltrim;
use function sprintf;
use function strtolower;
use function ucfirst;

final class SessionConfig
{
    private string $path;

    private string $cookie_name;

    private ?string $cookie_domain;

    /**
     * @var "Lax"|"None; Secure"|"Strict"
     */
    private string $same_site;

    private bool $http_only;

    private bool $only_secure;

    /**
     * @var positive-int|null
     */
    private ?int $absolute_lifetime_in_sec;

    private int $idle_timeout;

    private int $rotation_interval;

    private int $gc_percentage;

    /**
     * @param array{
     *     cookie_name:string,
     *     idle_timeout_in_sec: positive-int,
     *     rotation_interval_in_sec: positive-int,
     *     garbage_collection_percentage: int,
     *     absolute_lifetime_in_sec?: positive-int,
     *     domain?: string,
     *     same_site?: 'Lax'|'Strict'|'None',
     *     path?:string,
     *     http_only?: bool,
     *     secure?: bool
     * } $config
     */
    public function __construct(array $config)
    {
        $this->path = isset($config['path'])
            ? '/' . ltrim($config['path'], '/')
            : '/';

        if (! isset($config['cookie_name'])) {
            throw new InvalidArgumentException('A cookie name is required');
        }

        $this->cookie_name = $config['cookie_name'];

        $this->absolute_lifetime_in_sec = $config['absolute_lifetime_in_sec'] ?? null;

        if (! isset($config['idle_timeout_in_sec'])) {
            throw new InvalidArgumentException('An idle timeout is required.');
        }

        $this->idle_timeout = $config['idle_timeout_in_sec'];

        $this->cookie_domain = $config['domain'] ?? null;

        $same_site = ucfirst(strtolower($config['same_site'] ?? 'Lax'));
        if ('None' === $same_site) {
            $same_site = 'None; Secure';
        }

        if (! in_array($same_site, $req = ['Lax', 'Strict', 'None; Secure'], true)) {
            throw new InvalidArgumentException(sprintf('same_site must be one of [%s].', implode(', ', $req)));
        }

        $this->same_site = $same_site;

        if (! isset($config['rotation_interval_in_sec'])) {
            throw new InvalidArgumentException('A rotation interval is required.');
        }

        $this->rotation_interval = $config['rotation_interval_in_sec'];

        $gc_percentage = $config['garbage_collection_percentage'] ?? -1;
        if ($gc_percentage < 0 || $gc_percentage > 100) {
            throw new InvalidArgumentException('The garbage collection percentage has to be between 0 and 100.');
        }

        $this->gc_percentage = $gc_percentage;

        $this->http_only = $config['http_only'] ?? true;
        $this->only_secure = $config['secure'] ?? true;
    }

    public static function fromDefaults(string $cookie_name): SessionConfig
    {
        return new SessionConfig([
            'path' => '/',
            'cookie_name' => $cookie_name,
            'http_only' => true,
            'secure' => true,
            'same_site' => 'Lax',
            'idle_timeout_in_sec' => 60 * 15,
            'rotation_interval_in_sec' => 60 * 10,
            'garbage_collection_percentage' => 2,
        ]);
    }

    /**
     * @param array{
     *     cookie_name?:string,
     *     idle_timeout_in_sec?: positive-int,
     *     rotation_interval_in_sec?: positive-int,
     *     garbage_collection_percentage?: int,
     *     absolute_lifetime_in_sec?: positive-int,
     *     domain?: string,
     *     same_site?: 'Lax'|'Strict'|'None',
     *     path?:string,
     *     http_only?: bool,
     *     secure?: bool
     * } $config
     */
    public static function mergeDefaults(string $cookie_name, array $config): SessionConfig
    {
        return new SessionConfig(
            array_replace([
                'path' => '/',
                'cookie_name' => $cookie_name,
                'http_only' => true,
                'secure' => true,
                'same_site' => 'Lax',
                'idle_timeout_in_sec' => 60 * 15,
                'rotation_interval_in_sec' => 60 * 10,
                'garbage_collection_percentage' => 2,
            ], $config)
        );
    }

    public function cookiePath(): string
    {
        return $this->path;
    }

    public function cookieName(): string
    {
        return $this->cookie_name;
    }

    public function cookieDomain(): ?string
    {
        return $this->cookie_domain;
    }

    /**
     * @return "Lax"|"None; Secure"|"Strict"
     */
    public function sameSite(): string
    {
        return $this->same_site;
    }

    public function onlyHttp(): bool
    {
        return $this->http_only;
    }

    public function onlySecure(): bool
    {
        return $this->only_secure;
    }

    /**
     * @return positive-int|null
     */
    public function absoluteLifetimeInSec(): ?int
    {
        return $this->absolute_lifetime_in_sec;
    }

    public function idleTimeoutInSec(): int
    {
        return $this->idle_timeout;
    }

    public function rotationInterval(): int
    {
        return $this->rotation_interval;
    }

    /**
     * @interal
     */
    public function gcLottery(): SessionLottery
    {
        return new SessionLottery($this->gc_percentage);
    }
}
