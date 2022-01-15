<?php

declare(strict_types=1);

namespace Tests\Session;

use Carbon\Carbon;
use DateTimeImmutable;
use Codeception\TestCase\WPTestCase;
use Snicco\Session\Contracts\SessionClock;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Exceptions\BadSessionID;
use Snicco\Session\ValueObjects\SerializedSessionData;

abstract class SessionDriverTest extends WPTestCase
{
    
    /**
     * @var TestClock
     */
    protected $test_clock;
    
    protected function setUp() :void
    {
        $this->test_clock = new TestClock(Carbon::now());
        parent::setUp();
    }
    
    protected function tearDown() :void
    {
        wp_set_current_user(0);
        parent::tearDown();
    }
    
    /** @test */
    public function read_from_session_throws_exception_for_bad_id()
    {
        $driver = $this->createDriver($this->test_clock);
        
        $driver->write(
            'id1',
            SerializedSessionData::fromArray(['foo' => 'bar'], time())
        );
        
        $this->expectException(BadSessionID::class);
        
        $this->assertSame(null, $driver->read('id2'));
    }
    
    /** @test */
    public function last_activity_is_stored_correctly()
    {
        $driver = $this->createDriver($this->test_clock);
        $now = new DateTimeImmutable('2000-01-01');
        
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], $now->getTimestamp())
        );
        
        $data = $driver->read('session1');
        
        $this->assertSame($data->lastActivity()->getTimestamp(), $now->getTimestamp());
    }
    
    /** @test */
    public function data_can_be_read_from_the_session()
    {
        $driver = $this->createDriver($this->test_clock);
        
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], time())
        );
        
        $data = $driver->read('session1');
        $this->assertInstanceOf(SerializedSessionData::class, $data);
        
        $this->assertSame(['foo' => 'bar'], $data->asArray());
    }
    
    /** @test */
    public function data_can_be_written_to_the_session()
    {
        $driver = $this->createDriver($this->test_clock);
        
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], time())
        );
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'baz'], time())
        );
        
        $this->assertSame(['foo' => 'baz'], $driver->read('session1')->asArray());
    }
    
    /** @test */
    public function a_session_can_be_destroyed()
    {
        $driver = $this->createDriver($this->test_clock);
        
        $driver->write('session1', SerializedSessionData::fromArray([], time()));
        
        $driver->destroy(['session1']);
        
        $this->expectException(BadSessionID::class);
        $driver->read('session1');
    }
    
    /** @test */
    public function multiple_session_ids_can_be_destroyed()
    {
        $driver = $this->createDriver($this->test_clock);
        
        $driver->write('session1', SerializedSessionData::fromArray([], time()));
        $driver->write('session2', SerializedSessionData::fromArray([], time()));
        
        $driver->destroy(['session1', 'session2']);
        
        try {
            $driver->read('session1');
            $this->fail('Session [session1] should not have been read.');
        } catch (BadSessionID $e) {
            //
        }
        try {
            $driver->read('session2');
            $this->fail('Session [session2] should not have been read.');
        } catch (BadSessionID $e) {
            //
        }
    }
    
    /** @test */
    public function garbage_collection_works_for_old_sessions()
    {
        $driver = $this->createDriver($this->test_clock);
        
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], time())
        );
        $driver->gc(10);
        $this->assertSame(['foo' => 'bar'], $driver->read('session1')->asArray());
        
        $this->test_clock->setCurrentTime($c = Carbon::now()->addSecond());
        
        $driver->write(
            'session2',
            SerializedSessionData::fromArray(
                ['foo' => 'baz'],
                $c->getTimestamp()
            )
        );
        
        $this->test_clock->setCurrentTime(Carbon::now()->addSeconds(11));
        
        $driver->gc(10);
        
        $this->assertSame(['foo' => 'baz'], $driver->read('session2')->asArray());
        
        $this->expectException(BadSessionID::class);
        $driver->read('session1');
    }
    
    /** @test */
    public function touching_the_session_activity_works()
    {
        $driver = $this->createDriver($this->test_clock);
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], $now = time())
        );
        
        $this->assertSame(
            $now,
            $driver->read('session1')->lastActivity()->getTimestamp()
        );
        
        $driver->touch(
            'session1',
            DateTimeImmutable::createFromMutable($carbon = Carbon::now()->addSecond())
        );
        
        $this->assertSame(
            $carbon->getTimestamp(),
            $driver->read('session1')->lastActivity()->getTimestamp(),
            "session was not touched correctly."
        );
    }
    
    ///** @test */
    //public function all_session_for_a_given_user_id_can_be_retrieved()
    //{
    //    $driver = $this->createDriver($this->test_clock);
    //    $user1 = $this->factory()->user->create();
    //    $user2 = $this->factory()->user->create();
    //    wp_set_current_user($user1);
    //
    //    $driver->write(
    //        $id1 = SessionId::createFresh()->asString(),
    //        SerializedSessionData::fromArray(['foo' => 'bar'], )
    //    );
    //    $driver->write(
    //        $id2 = SessionId::createFresh()->asString(),
    //        SerializedSessionData::fromArray(['foo' => 'baz'])
    //    );
    //
    //    wp_set_current_user($user2);
    //    $driver->write(
    //        $id3 = SessionId::createFresh()->asString(),
    //        SerializedSessionData::fromArray(['foo' => 'bam'])
    //    );
    //
    //    $sessions = $driver->getAllByUserId($user1);
    //
    //    $this->assertInstanceOf(SessionInfos::class, $sessions);
    //    $this->assertCount(2, $sessions);
    //    $this->assertSame(serialize(['foo' => 'bar']), $sessions[$id1]->storedData());
    //    $this->assertSame(serialize(['foo' => 'baz']), $sessions[$id2]->storedData());
    //}
    
    ///** @test */
    //public function all_sessions_but_the_one_with_the_provided_token_can_be_destroyed_for_the_user()
    //{
    //    $driver = $this->createDriver($this->test_clock);
    //    $user1 = $this->factory()->user->create();
    //    $user2 = $this->factory()->user->create();
    //
    //    wp_set_current_user($user1);
    //    $driver->write('session1', SerializedSessionData::fromArray(['foo' => 'bar']));
    //    $driver->write('session2', SerializedSessionData::fromArray(['foo' => 'baz']));
    //
    //    wp_set_current_user($user2);
    //    $driver->write('session3', SerializedSessionData::fromArray(['foo' => 'bam']));
    //
    //    $driver->destroyOthersForUser('session1', $user1);
    //
    //    $this->assertSame(['foo' => 'bar'], $driver->read('session1')->asArray());
    //    $this->assertSame(['foo' => 'bam'], $driver->read('session3')->asArray());
    //
    //    $this->expectException(CantReadSessionContent::class);
    //    $driver->read('session2');
    //}
    
    ///** @test */
    //public function all_sessions_for_a_user_can_be_destroyed()
    //{
    //    $driver = $this->createDriver($this->test_clock);
    //    $user1 = $this->factory()->user->create();
    //    $user2 = $this->factory()->user->create();
    //
    //    wp_set_current_user($user1);
    //    $driver->write('session1', SerializedSessionData::fromArray([]));
    //    $driver->write('session2', SerializedSessionData::fromArray([]));
    //
    //    wp_set_current_user($user2);
    //    $driver->write('session3', SerializedSessionData::fromArray([]));
    //
    //    $driver->destroyAllForUser($user1);
    //
    //    $this->assertInstanceOf(SerializedSessionData::class, $driver->read('session3'));
    //
    //    try {
    //        $driver->read('session1');
    //        $this->fail("Session [session1] should not have been read.");
    //    } catch (CantReadSessionContent $e) {
    //        //
    //    }
    //
    //    try {
    //        $driver->read('session2');
    //        $this->fail("Session [baz] should not have been read.");
    //    } catch (CantReadSessionContent $e) {
    //        //
    //    }
    //}
    
    ///** @test */
    //public function all_sessions_can_be_destroyed()
    //{
    //    $driver = $this->createDriver($this->test_clock);
    //    $user1 = $this->factory()->user->create();
    //    $user2 = $this->factory()->user->create();
    //
    //    wp_set_current_user($user1);
    //    $driver->write('session1', SerializedSessionData::fromArray([]));
    //    $driver->write('session2', SerializedSessionData::fromArray([]));
    //
    //    wp_set_current_user($user2);
    //    $driver->write('session3', SerializedSessionData::fromArray([]));
    //
    //    $driver->destroyAll();
    //
    //    try {
    //        $this->assertSame(null, $driver->read('session1'));
    //        $this->fail('Session [session1] should not have been read.');
    //    } catch (CantReadSessionContent $e) {
    //        //
    //    }
    //
    //    try {
    //        $this->assertSame(null, $driver->read('session2'));
    //        $this->fail('Session [session2] should not have been read.');
    //    } catch (CantReadSessionContent $e) {
    //        //
    //    }
    //
    //    try {
    //        $this->assertSame(null, $driver->read('session3'));
    //        $this->fail('Session [session3] should not have been read.');
    //    } catch (CantReadSessionContent $e) {
    //        //
    //    }
    //}
    
    abstract protected function createDriver(SessionClock $clock) :SessionDriver;
    
}