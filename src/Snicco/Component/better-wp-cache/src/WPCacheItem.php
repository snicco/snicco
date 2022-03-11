<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use Snicco\Component\BetterWPCache\Exception\Psr6InvalidArgumentException;

use function gettype;
use function is_int;
use function sprintf;

/**
 * @psalm-internal Snicco\Component\BetterWPCache
 *
 * @interal
 */
final class WPCacheItem implements CacheItemInterface
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var non-empty-string
     */
    private string $key;

    private bool $is_hit;

    private ?int $expiration_timestamp = null;

    /**
     * @param non-empty-string $key
     * @param mixed            $value
     */
    public function __construct(string $key, $value, bool $is_hit)
    {
        $this->key = $key;
        $this->is_hit = $is_hit;
        $this->value = $value;
    }

    /**
     * @return non-empty-string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    public function get()
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->is_hit;
    }

    public function set($value): self
    {
        $this->value = $value;
        $this->is_hit = true;

        return $this;
    }

    public function expiresAt($expiration): self
    {
        if ($expiration instanceof DateTimeInterface) {
            $this->expiration_timestamp = $expiration->getTimestamp();
        } elseif (null === $expiration) {
            $this->expiration_timestamp = $expiration;
        } else {
            throw new Psr6InvalidArgumentException(
                sprintf(
                    "Cache item ttl/expiresAfter must be \\DateInterval or NULL.\nGot [%s]",
                    gettype($expiration)
                )
            );
        }

        return $this;
    }

    public function expiresAfter($time): self
    {
        if (null === $time) {
            $this->expiration_timestamp = null;
        } elseif ($time instanceof DateInterval) {
            $date = new DateTime();
            $date->add($time);
            $this->expiration_timestamp = $date->getTimestamp();
        } elseif (is_int($time)) {
            $this->expiration_timestamp = time() + $time;
        } else {
            throw new Psr6InvalidArgumentException(
                sprintf(
                    "Cache item ttl/expiresAfter must be of type integer or ?\\DateInterval.\nGot [%s]",
                    gettype($this)
                )
            );
        }

        return $this;
    }

    public function expirationTimestamp(): ?int
    {
        return $this->expiration_timestamp;
    }

    /**
     * @param non-empty-string $key
     */
    public static function miss(string $key): WPCacheItem
    {
        return new self($key, null, false);
    }
}
