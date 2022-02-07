<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\SessionManager;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\SessionManager\SingleSessionSessionManager;
use Snicco\Component\Session\Tests\fixtures\SessionHelpers;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\SessionConfig;

final class SingleSessionManagerTest extends TestCase
{

    use SessionHelpers;

    /**
     * @test
     */
    public function multiple_calls_to_get_session_return_the_same_instance(): void
    {
        $manager = new SingleSessionSessionManager($this->getSessionManager());

        $session1 = $manager->start(CookiePool::fromSuperGlobals());
        $session2 = $manager->start(CookiePool::fromSuperGlobals());

        $this->assertSame($session1, $session2);
    }

    /**
     * @test
     */
    public function test_save(): void
    {
        $driver = new InMemoryDriver();
        $this->assertCount(0, $driver->all());
        $manager = new SingleSessionSessionManager($this->getSessionManager(null, $driver));

        $session1 = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($session1);

        $this->assertCount(1, $driver->all());
    }

    /**
     * @test
     */
    public function test_toCookie(): void
    {
        $manager = new SingleSessionSessionManager($this->getSessionManager());

        $session1 = $manager->start(CookiePool::fromSuperGlobals());

        $cookie = $manager->toCookie($session1);
        $this->assertSame($session1->id()->asString(), $cookie->value());
    }

    /**
     * @test
     */
    public function test_cookie_has_lifetime_of_zero_if_not_set_in_config(): void
    {
        $config = new SessionConfig([
            'cookie_name' => 'test',
            'idle_timeout_in_sec' => 10,
            'rotation_interval_in_sec' => 20,
            'garbage_collection_percentage' => 2
        ]);

        $manager = new SingleSessionSessionManager($this->getSessionManager($config));

        $session1 = $manager->start(CookiePool::fromSuperGlobals());

        $cookie = $manager->toCookie($session1);
        $this->assertSame('test', $cookie->name());
        $this->assertSame(null, $cookie->lifetime());
        $this->assertSame(0, $cookie->expiryTimestamp());
    }

}