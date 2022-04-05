<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionWP\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Bridge\SessionWP\WPObjectCacheDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;

use function sleep;

/**
 * @internal
 */
final class WPObjectCacheDriverTest extends WPTestCase
{
    use SessionDriverTests;

    protected function createDriver(Clock $clock): SessionDriver
    {
        return new WPObjectCacheDriver('my_sessions', $this->idleTimeout());
    }

    /**
     * @param 0|positive-int $seconds
     */
    protected function travelIntoFuture(TestClock $clock, int $seconds): void
    {
        sleep($seconds);
    }
}
