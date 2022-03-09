<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionWP\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Bridge\SessionWP\WPDBSessionDriver;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Driver\UserSessionsDriver;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\Session\Testing\UserSessionDriverTests;
use Snicco\Component\TestableClock\Clock;

/**
 * @internal
 */
final class WPDBSessionDriverTest extends WPTestCase
{
    use SessionDriverTests;
    use UserSessionDriverTests;

    private BetterWPDB $better_wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->better_wpdb = BetterWPDB::fromWpdb();
        $this->better_wpdb->unprepared('DROP TABLE IF EXISTS session_testing');
        (new WPDBSessionDriver('session_testing', $this->better_wpdb))->createTable();
    }

    protected function tearDown(): void
    {
        $this->better_wpdb->unprepared('DROP TABLE IF EXISTS session_testing');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_create_table(): void
    {
        $this->better_wpdb->unprepared('DROP TABLE IF EXISTS session_testing');

        $driver = new WPDBSessionDriver('session_testing', $this->better_wpdb);

        $driver->createTable();

        $exists = $this->better_wpdb->selectValue(
            'select exists(select * from information_schema.TABLES where TABLE_NAME = ?)',
            ['session_testing']
        );

        $this->assertSame(1, $exists);
    }

    protected function createDriver(Clock $clock): SessionDriver
    {
        return new WPDBSessionDriver('session_testing', $this->better_wpdb, $clock);
    }

    protected function createUserSessionDriver(array $user_sessions): UserSessionsDriver
    {
        $driver = new WPDBSessionDriver('session_testing', $this->better_wpdb);

        foreach ($user_sessions as $selector => $user_session) {
            $driver->write($selector, $user_session);
        }

        return $driver;
    }
}
