<?php

declare(strict_types=1);

namespace Tests\Session\integration;

use Carbon\Carbon;
use DateTimeImmutable;
use Snicco\Session\Session;
use Tests\Session\TestClock;
use Snicco\Session\SessionManager;
use Codeception\TestCase\WPTestCase;
use Snicco\Session\Events\SessionRotated;
use Snicco\Session\ValueObjects\SessionId;
use Snicco\Session\Contracts\SessionClock;
use Snicco\Session\Exceptions\BadSessionID;
use Snicco\Session\ValueObjects\CookiePool;
use Snicco\Session\ValueObjects\SessionConfig;
use Snicco\Session\ValueObjects\SessionCookie;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Session\EventDispatcherUsingWPHooks;
use Snicco\Session\Contracts\SessionEventDispatcher;
use Snicco\Session\ValueObjects\SerializedSessionData;
use Snicco\Session\ValueObjects\ClockUsingDateTimeImmutable;

final class SessionManagerTest extends WPTestCase
{
    
    private $absolute_lifetime        = 10;
    private $idle_timeout             = 3;
    private $gc_collection_percentage = 0;
    private $rotation_interval        = 5;
    
    private $cookie_name = 'sniccowp_session';
    
    /**
     * @var ArraySessionDriver
     */
    private $driver;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->driver = new ArraySessionDriver();
        unset($_COOKIE[$this->cookie_name]);
    }
    
    protected function tearDown() :void
    {
        unset($_COOKIE[$this->cookie_name]);
        parent::tearDown();
    }
    
    /** @test */
    public function starting_a_session_without_an_existing_id_works()
    {
        $manager = $this->getSessionManager();
        
        $session = $manager->start(CookiePool::fromSuperGlobals());
        
        $this->assertInstanceOf(Session::class, $session);
    }
    
    /** @test */
    public function starting_a_session_with_a_wrong_id_will_generate_a_new_id()
    {
        $_COOKIE[$this->cookie_name] = 'foobar';
        
        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());
        
        $this->assertInstanceOf(Session::class, $session);
        $this->assertNotSame('foobar', $session->id());
    }
    
    /** @test */
    public function retrieving_the_session_twice_will_not_return_the_same_object_reference()
    {
        $manager = $this->getSessionManager();
        $session1 = $manager->start(CookiePool::fromSuperGlobals());
        $session2 = $manager->start(CookiePool::fromSuperGlobals());
        
        $this->assertNotSame($session1, $session2);
    }
    
    /** @test */
    public function data_can_be_read_from_the_session_driver()
    {
        $id = $this->writeSessionWithData(['foo' => 'bar']);
        
        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());
        
        $this->assertSame('bar', $session->all()['foo']);
        $this->assertSame($id->asString(), $session->id()->asString());
    }
    
    /** @test */
    public function updated_session_data_is_saved_to_the_driver()
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
    
    /** @test */
    public function the_session_cookie_has_the_same_id_as_the_active_session()
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
    
    /** @test */
    public function invalidating_deletes_the_old_session_and_clears_the_current_session()
    {
        $old_id = $this->writeSessionWithData(['foo' => 'bar']);
        
        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());
        
        $session->invalidate();
        $manager->save($session);
        
        $data = $this->driver->read($session->id()->asHash())->asArray();
        $this->assertArrayNotHasKey('foo', $data);
        
        $this->expectException(BadSessionID::class);
        $this->driver->read($old_id->asHash());
    }
    
    /** @test */
    public function the_old_session_is_deleted_after_migrating_a_session()
    {
        $old_id = $this->writeSessionWithData(['foo' => 'bar']);
        
        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $session->rotate();
        $manager->save($session);
        $new_id = $session->id();
        
        $this->assertNotNull($this->driver->read($new_id->asHash()));
        $this->expectException(BadSessionID::class);
        $this->driver->read($old_id->asHash());
    }
    
    /** @test */
    public function an_idle_session_is_not_started()
    {
        $this->writeSessionWithData(['foo' => 'bar']);
        
        $test_clock = new TestClock(Carbon::now()->addSeconds($this->idle_timeout));
        
        $manager = $this->getSessionManager($test_clock);
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $this->assertSame('bar', $session->get('foo'));
        
        $test_clock = new TestClock(Carbon::now()->addSeconds($this->idle_timeout + 1));
        
        $manager = $this->getSessionManager($test_clock);
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $this->assertSame(null, $session->get('foo'));
    }
    
    /** @test */
    public function a_session_with_activity_can_not_be_started_after_it_has_expired_absolutely()
    {
        $this->absolute_lifetime = 6;
        $this->idle_timeout = 3;
        // We are not interested in this here
        $this->rotation_interval = 1000;
        
        // Session creation time equals system time.
        $this->writeSessionWithData(['foo' => 'bar']);
        
        // The session is about to expire
        $test_time = Carbon::now()->addSeconds($this->idle_timeout);
        $manager = $this->getSessionManager(new TestClock($test_time));
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($session);
        
        $this->assertSame('bar', $session->get('foo'));
        
        // The session is about to expire again, but we don't exceed the 3 sec idle timeout.
        $test_time = Carbon::now()->addSeconds($this->absolute_lifetime);
        $manager = $this->getSessionManager(new TestClock($test_time));
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($session);
        
        $this->assertSame('bar', $session->get('foo'));
        
        // The session is still active, only one seconds has passed since the last save() but
        // its expired absolutely
        $test_time = Carbon::now()->addSeconds($this->absolute_lifetime + 1);
        $manager = $this->getSessionManager(new TestClock($test_time));
        $session = $manager->start(CookiePool::fromSuperGlobals());
        
        $this->assertSame(null, $session->get('foo'));
    }
    
    /** @test */
    public function session_ids_are_rotated_if_needed()
    {
        $count = 0;
        add_action(SessionRotated::class, function (SessionRotated $event) use (&$count) {
            $count++;
            $this->assertSame('bar', $event->session->get('foo'));
        });
        
        // Irrelevant here
        $this->idle_timeout = 1000;
        
        $old_id = $this->writeSessionWithData(['foo' => 'bar']);
        $old_session =
            $this->getSessionManager()->start(CookiePool::fromSuperGlobals());
        
        $test_clock = new TestClock(
            Carbon::now()->addSeconds($this->rotation_interval)
        );
        
        $manager = $this->getSessionManager($test_clock);
        $new_session = $manager->start(CookiePool::fromSuperGlobals());
        $new_id = $new_session->id();
        
        $this->assertTrue($old_id->sameAs($new_id));
        $this->assertSame(0, $count);
        
        $test_clock = new TestClock(
            Carbon::now()->addSeconds($this->rotation_interval + 1)
        );
        
        $manager = $this->getSessionManager($test_clock);
        $new_session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($new_session);
        
        $new_id = $new_session->id();
        
        $this->assertSame('bar', $new_session->get('foo'));
        $this->assertSame($old_session->createdAt(), $new_session->createdAt());
        $this->assertSame($test_clock->currentTimestamp(), $new_session->lastRotation());
        $this->assertFalse($old_id->sameAs($new_id));
        $this->assertSame(1, $count);
    }
    
    /** @test */
    public function garbage_collection_works()
    {
        $test_clock = new TestClock(Carbon::now());
        $this->driver = new ArraySessionDriver($test_clock);
        $this->gc_collection_percentage = 100;
        
        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());
        
        $manager->save($session);
        $old_id = $session->id();
        
        $test_clock->setCurrentTime(Carbon::now()->addSeconds($this->idle_timeout));
        $manager = $this->getSessionManager();
        $session = $manager->start(CookiePool::fromSuperGlobals());
        
        $manager->save($session);
        
        $this->assertNotNull($this->driver->read($old_id->asHash()));
        
        $test_clock->setCurrentTime(Carbon::now()->addSeconds($this->idle_timeout + 1));
        $manager = $this->getSessionManager($test_clock);
        $session = $manager->start(CookiePool::fromSuperGlobals());
        $manager->save($session);
        
        $this->expectException(BadSessionID::class);
        $this->driver->read($old_id->asHash());
    }
    
    /** @test */
    public function testSessionCookie()
    {
        $manager = $this->getSessionManager(null, [
            'cookie_name' => 'foobar_cookie',
            'path' => '/foo',
            'same_site' => 'lax',
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
    
    private function getSessionManager(SessionClock $clock = null, array $config = null, SessionEventDispatcher $event_dispatcher = null) :SessionManager
    {
        $default = [
            'path' => '/',
            'cookie_name' => $this->cookie_name,
            'domain' => 'foo.com',
            'http_only' => true,
            'secure' => true,
            'same_site' => 'lax',
            'absolute_lifetime_in_sec' => $this->absolute_lifetime,
            'idle_timeout_in_sec' => $this->idle_timeout,
            'rotation_interval_in_sec' => $this->rotation_interval,
            'garbage_collection_percentage' => $this->gc_collection_percentage,
        ];
        
        if ($config) {
            $default = array_merge($default, $config);
        }
        
        return new SessionManager(
            new SessionConfig($default),
            $this->driver,
            $clock ?? new ClockUsingDateTimeImmutable(),
            $event_dispatcher ?? new EventDispatcherUsingWPHooks()
        );
    }
    
    private function now() :int
    {
        return (new DateTimeImmutable())->getTimestamp();
    }
    
    private function writeSessionWithData(array $data) :SessionId
    {
        $manager = $this->getSessionManager();
        $id = SessionId::createFresh();
        $data = SerializedSessionData::fromArray($data, $this->now());
        
        $session = new Session($id, $data->asArray(), $data->lastActivity());
        $manager->save($session);
        
        $_COOKIE[$this->cookie_name] = $id->asString();
        return $id;
    }
    
}

