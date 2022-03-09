<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\Drivers;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Driver\UserSessionsDriver;
use Snicco\Component\Session\Exception\CouldNotDestroySessions;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\Session\Testing\UserSessionDriverTests;
use Snicco\Component\TestableClock\Clock;

/**
 * @internal
 */
final class InMemoryDriverTest extends TestCase
{
    use SessionDriverTests;
    use UserSessionDriverTests;

    /**
     * @test
     */
    public function garbage_collection_can_be_forced_to_fail(): void
    {
        $driver = new InMemoryDriver(null, true);
        $this->expectException(CouldNotDestroySessions::class);
        $this->expectExceptionMessage('force-failed');
        $driver->gc(10);
    }

    protected function createDriver(Clock $clock): SessionDriver
    {
        return new InMemoryDriver($clock);
    }

    protected function createUserSessionDriver(array $user_sessions): UserSessionsDriver
    {
        $driver = new InMemoryDriver();

        foreach ($user_sessions as $selector => $user_session) {
            $driver->write($selector, $user_session);
        }

        return $driver;
    }
}
