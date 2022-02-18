<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\SessionManager;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Event\SessionRotated;
use Snicco\Component\Session\EventDispatcher\NullSessionDispatcher;
use Snicco\Component\Session\EventDispatcher\SessionEventDispatcher;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\ReadWriteSession;
use Snicco\Component\Session\Serializer\JsonSerializer;
use Snicco\Component\Session\SessionManager\FactorySessionManager;
use Snicco\Component\Session\Tests\fixtures\TestEventDispatcher;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\SessionConfig;
use Snicco\Component\Session\ValueObject\SessionCookie;
use Snicco\Component\Session\ValueObject\SessionId;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Component\TestableClock\TestClock;

use function explode;
use function strlen;
use function substr;
use function time;

final class FactorySessionManagerTest extends TestCase
{

    /**
     * @var positive-int
     */
    private int $absolute_lifetime = 10;

    /**
     * @var positive-int
     */
    private int $idle_timeout = 3;

    private int $gc_collection_percentage = 0;

    /**
     * @var positive-int
     */
    private int $rotation_interval = 5;

    private string $cookie_name = 'sniccowp_session';

    private InMemoryDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new InMemoryDriver();
        unset($_COOKIE[$this->cookie_name]);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE[$this->cookie_name]);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function starting_a_session_without_an_existing_id_works(): void
    {
        $manager = $this->getSessionManager();

        $session = $manager->start(CookiePool::fromSuperGlobals());

        $this->assertInstanceOf(ReadWriteSession::class, $session);
    }

    /**
     * @test
     */
    public function starting_a_session_with_a_wrong_id_will_generate_a_new_id(): void
    {
        $_COOKIE[$this->cookie_name] = 'foobar';

        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());

        $this->assertInstanceOf(ReadWriteSession::class, $session);
        $this->assertNotSame('foobar', $session->id());
    }

    /**
     * @test
     */
    public function retrieving_the_session_twice_will_not_return_the_same_object_reference(): void
    {
        $manager = $this->getSessionManager();
        $session1 = $manager->start(CookiePool::fromSuperGlobals());
        $session2 = $manager->start(CookiePool::fromSuperGlobals());

        $this->assertNotSame($session1, $session2);
    }

    /**
     * @test
     */
    public function data_can_be_read_from_the_session_driver(): void
    {
        $id = $this->writeSessionWithData(['foo' => 'bar']);

        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());

        $this->assertSame('bar', $session->all()['foo']);
        $this->assertSame($id->asString(), $session->id()->asString());
    }

    /**
     * @test
     */
    public function updated_session_data_is_saved_to_the_driver(): void
    {
        $id = $this->writeSessionWithData(['foo' => 'bar']);

        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());

        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame($id->asString(), $session->id()->asString());

        $session->put('baz', 'biz');

        $manager->save($session);

        $new_manager = $this->getSessionManager();
        $session = $new_manager->start(CookiePool::fromSuperGlobals());

        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame('biz', $session->get('baz'));
        $this->assertSame($id->asString(), $session->id()->asString());
    }

    /**
     * @test
     */
    public function the_session_cookie_has_the_same_id_as_the_active_session(): void
    {
        $id = $this->writeSessionWithData(['foo' => 'bar']);

        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($session);

        $cookie = $manager->toCookie($session);
        $this->assertInstanceOf(SessionCookie::class, $cookie);
        $this->assertSame($id->asString(), $cookie->value());
        $this->assertSame($this->cookie_name, $cookie->name());
    }

    /**
     * @test
     */
    public function invalidating_deletes_the_old_session_and_clears_the_current_session(): void
    {
        $old_id = $this->writeSessionWithData(['foo' => 'bar']);

        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());

        $session->invalidate();
        $manager->save($session);

        $new_session = $manager->start(new CookiePool([$this->cookie_name => $session->id()->asString()]));
        // id stays the same
        $this->assertTrue($new_session->id()->sameAs($session->id()));
        // data was flashed
        $this->assertFalse($new_session->has('foo'));

        // old id is gone.
        $this->expectException(BadSessionID::class);
        $this->driver->read($old_id->selector());
    }

    /**
     * @test
     */
    public function the_old_session_is_deleted_after_migrating_a_session(): void
    {
        $old_id = $this->writeSessionWithData(['foo' => 'bar']);

        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $session->rotate();
        $manager->save($session);
        $new_id = $session->id();

        $this->assertNotNull($this->driver->read($new_id->selector()));

        $this->expectException(BadSessionID::class);
        $this->driver->read($old_id->selector());
    }

    /**
     * @test
     */
    public function an_idle_session_is_not_started(): void
    {
        $this->writeSessionWithData(['foo' => 'bar']);

        $test_clock = new TestClock();
        $test_clock->travelIntoFuture($this->idle_timeout);

        $manager = $this->getSessionManager($test_clock);
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $this->assertSame('bar', $session->get('foo'));

        $test_clock = new TestClock();
        $test_clock->travelIntoFuture($this->idle_timeout + 1);

        $manager = $this->getSessionManager($test_clock);
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $this->assertSame(null, $session->get('foo'));
    }

    /**
     * @test
     */
    public function a_session_with_activity_can_not_be_started_after_it_has_expired_absolutely(): void
    {
        $this->absolute_lifetime = 6;
        $this->idle_timeout = 3;
        // We are not interested in this here
        $this->rotation_interval = 1000;

        // Session creation time equals system time.
        $this->writeSessionWithData(['foo' => 'bar']);

        // The session is about to expire because it's idle.
        $clock = new TestClock();
        $clock->travelIntoFuture($this->idle_timeout);
        $manager = $this->getSessionManager($clock);

        $session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($session);
        $this->assertSame('bar', $session->get('foo'));

        // The session is about to expire again, but we don't exceed the 3 sec idle timeout.
        $clock = new TestClock();
        $clock->travelIntoFuture($this->absolute_lifetime);
        $manager = $this->getSessionManager($clock);
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($session);

        $this->assertSame('bar', $session->get('foo'));

        // The session is still active, only one seconds has passed since the last save() but
        // its expired absolutely
        $clock = new TestClock();
        $clock->travelIntoFuture($this->absolute_lifetime + 1);
        $manager = $this->getSessionManager($clock);
        $session = $manager->start(CookiePool::fromSuperGlobals());

        $this->assertSame(null, $session->get('foo'));
    }

    /**
     * @test
     */
    public function session_ids_are_rotated_if_needed(): void
    {
        // Irrelevant here
        $this->idle_timeout = 1000;

        $old_id = $this->writeSessionWithData(['foo' => 'bar']);
        $old_session = $this->getSessionManager()->start(CookiePool::fromSuperGlobals());

        $test_clock = new TestClock();
        $test_clock->travelIntoFuture($this->rotation_interval);

        $manager = $this->getSessionManager($test_clock);
        $new_session = $manager->start(CookiePool::fromSuperGlobals());
        $new_id = $new_session->id();

        $this->assertTrue($old_id->sameAs($new_id));

        $test_clock->travelIntoFuture(1);

        $manager = $this->getSessionManager($test_clock);
        $new_session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($new_session);

        $new_id = $new_session->id();

        $this->assertSame('bar', $new_session->get('foo'));
        $this->assertSame($old_session->createdAt(), $new_session->createdAt());
        $this->assertSame($test_clock->currentTimestamp(), $new_session->lastRotation());
        $this->assertFalse($old_id->sameAs($new_id));
    }

    /**
     * @test
     */
    public function garbage_collection_works(): void
    {
        $test_clock = new TestClock();
        $this->driver = new InMemoryDriver($test_clock);
        $this->gc_collection_percentage = 100;

        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());

        $manager->save($session);
        $old_id = $session->id();

        $test_clock->travelIntoFuture($this->idle_timeout - 1);

        $manager->gc();

        $this->assertNotNull($this->driver->read($old_id->selector()));

        $test_clock->travelIntoFuture(1);

        $manager->gc();

        $this->expectException(BadSessionID::class);
        $this->driver->read($old_id->selector());
    }

    /**
     * @test
     */
    public function testSessionCookie(): void
    {
        $manager = $this->getSessionManager(null, [
            'cookie_name' => 'foobar_cookie',
            'path' => '/foo',
            'same_site' => 'Lax',
            'domain' => 'foo.com',
            'absolute_lifetime_in_sec' => 30,
        ]);

        $session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($session);

        $cookie = $manager->toCookie($session);

        $this->assertSame('foobar_cookie', $cookie->name());
        $this->assertSame($session->id()->asString(), $cookie->value());
        $this->assertSame('Lax', $cookie->sameSite());
        $this->assertSame('/foo', $cookie->path());
        $this->assertSame('foo.com', $cookie->domain());
        $this->assertTrue($cookie->secureOnly());
        $this->assertTrue($cookie->httpOnly());
        $this->assertSame(time() + 30, $cookie->expiryTimestamp());
        $this->assertSame(30, $cookie->lifetime());
    }

    /**
     * @test
     */
    public function session_events_are_recorded_and_dispatched(): void
    {
        // Irrelevant here
        $this->idle_timeout = 1000;

        $listened = false;
        $listener = function (SessionRotated $event) use (&$listened): void {
            $listened = true;
            $this->assertSame('bar', $event->session->get('foo'));
        };

        $event_dispatcher = new TestEventDispatcher([SessionRotated::class => $listener]);

        $old_id = $this->writeSessionWithData(['foo' => 'bar']);
        $old_session = $this->getSessionManager()->start(CookiePool::fromSuperGlobals());

        $clock = new TestClock();
        $clock->travelIntoFuture($this->rotation_interval + 1);

        $manager = $this->getSessionManager($clock, null, $event_dispatcher);
        $new_session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($new_session);

        $new_id = $new_session->id();

        $this->assertSame('bar', $new_session->get('foo'));
        $this->assertSame($old_session->createdAt(), $new_session->createdAt());
        $this->assertSame($clock->currentTimestamp(), $new_session->lastRotation());
        $this->assertFalse($old_id->sameAs($new_id));
        $this->assertSame(true, $listened);
    }

    /**
     * @test
     */
    public function test_exception_if_provided_and_stored_validator_dont_match(): void
    {
        $id = $this->writeSessionWithData(['foo' => 'bar']);

        /** @var array{0:string, 1:string } $parts */
        $parts = explode('|', $id->asString());
        $tampered = $parts[0] . '|' . 'tempered' . (string)substr($parts[1], strlen('tempered'));

        $_COOKIE[$this->cookie_name] = $tampered;

        try {
            $this->getSessionManager()->start(CookiePool::fromSuperGlobals());
            $this->fail('Invalid session validator accepted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                "Possible session brute force attack.\nHashed validator did not match for session selector [{$parts[0]}].",
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_selector_is_deleted_if_the_hashed_validator_does_not_match(): void
    {
        $id = $this->writeSessionWithData(['foo' => 'bar']);

        /** @var array{0:string, 1:string } $parts */
        $parts = explode('|', $id->asString());
        $tampered = $parts[0] . '|' . 'tempered' . (string)substr($parts[1], strlen('tempered'));

        $_COOKIE[$this->cookie_name] = $tampered;

        try {
            $this->getSessionManager()->start(CookiePool::fromSuperGlobals());
            $this->fail('Invalid session validator accepted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                "Possible session brute force attack.\nHashed validator did not match for session selector [{$parts[0]}].",
                $e->getMessage()
            );
        }

        $this->expectException(BadSessionID::class);
        $this->driver->read($id->selector());
    }

    /**
     * @param null|array{
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
    private function getSessionManager(
        Clock $clock = null,
        array $config = null,
        SessionEventDispatcher $event_dispatcher = null
    ): FactorySessionManager {
        $default = [
            'path' => '/',
            'cookie_name' => $this->cookie_name,
            'domain' => 'foo.com',
            'http_only' => true,
            'secure' => true,
            'same_site' => 'Lax',
            'absolute_lifetime_in_sec' => $this->absolute_lifetime,
            'idle_timeout_in_sec' => $this->idle_timeout,
            'rotation_interval_in_sec' => $this->rotation_interval,
            'garbage_collection_percentage' => $this->gc_collection_percentage,
        ];

        if ($config) {
            $default = array_merge($default, $config);
        }

        return new FactorySessionManager(
            new SessionConfig($default),
            $this->driver,
            new JsonSerializer(),
            $clock ?? new SystemClock(),
            $event_dispatcher ?? new NullSessionDispatcher()
        );
    }

    private function writeSessionWithData(array $data): SessionId
    {
        $manager = $this->getSessionManager();
        $id = SessionId::new();
        $session = new ReadWriteSession($id, $data, time());
        $manager->save($session);

        $_COOKIE[$this->cookie_name] = $id->asString();
        return $id;
    }


}

