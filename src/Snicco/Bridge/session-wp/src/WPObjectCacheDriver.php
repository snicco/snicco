<?php

declare(strict_types=1);


namespace Snicco\Bridge\SessionWP;

use Snicco\Bridge\SessionPsr16\Psr16SessionDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\ValueObject\SerializedSession;

final class WPObjectCacheDriver implements SessionDriver
{
    private Psr16SessionDriver $driver;

    public function __construct(Psr16SessionDriver $driver)
    {
        $this->driver = $driver;
    }

    public function read(string $selector): SerializedSession
    {
        return $this->driver->read($selector);
    }

    public function write(string $selector, SerializedSession $session): void
    {
        $this->driver->write($selector, $session);
    }

    public function destroy(array $selectors): void
    {
        $this->driver->destroy($selectors);
    }

    public function gc(int $seconds_without_activity): void
    {
        $this->driver->gc($seconds_without_activity);
    }

    public function touch(string $selector, int $current_timestamp): void
    {
        $this->driver->touch($selector, $current_timestamp);
    }
}