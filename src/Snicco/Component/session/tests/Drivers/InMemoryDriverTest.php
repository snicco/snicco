<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\Drivers;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Driver\UserSessionsDriver;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\Session\Testing\UserSessionDriverTests;
use Snicco\Component\TestableClock\Clock;

final class InMemoryDriverTest extends TestCase
{

    use SessionDriverTests;
    use UserSessionDriverTests;

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