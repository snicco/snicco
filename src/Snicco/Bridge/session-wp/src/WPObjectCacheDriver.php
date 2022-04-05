<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionWP;

use Snicco\Bridge\SessionPsr16\Psr16SessionDriver;
use Snicco\Component\BetterWPCache\CacheFactory;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\ValueObject\SerializedSession;

final class WPObjectCacheDriver implements SessionDriver
{
    private Psr16SessionDriver $driver;

    /**
     * @param non-empty-string $cache_group
     */
    public function __construct(string $cache_group, int $idle_timeout_in_seconds)
    {
        $this->driver = new Psr16SessionDriver(CacheFactory::psr16($cache_group), $idle_timeout_in_seconds);
    }

    public function read(string $selector): SerializedSession
    {
        return $this->driver->read($selector);
    }

    public function write(string $selector, SerializedSession $session): void
    {
        $this->driver->write($selector, $session);
    }

    public function destroy(string $selector): void
    {
        $this->driver->destroy($selector);
    }

    public function gc(int $seconds_without_activity): void
    {
    }

    public function touch(string $selector, int $current_timestamp): void
    {
        $this->driver->touch($selector, $current_timestamp);
    }
}
