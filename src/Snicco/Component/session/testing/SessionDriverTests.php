<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Testing;

use DateTimeImmutable;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\ValueObject\SerializedSessionData;

/**
 * @todo This should be tested with the real time() function since the psr cache interfaces
 *       don't use our clock interface. This should be refactored once we use the phpunit
 *       test runner to the symfony/phpunit-bridge
 */
trait SessionDriverTests
{
    
    /** @test */
    public function read_from_session_throws_exception_for_bad_id()
    {
        $driver = $this->createDriver(new TestClock());
        
        $driver->write(
            'id1',
            SerializedSessionData::fromArray(['foo' => 'bar'], time())
        );
        
        try {
            $driver->read('id2');
            PHPUnit::fail("An exception should have been thrown for reading a bad session id.");
        } catch (BadSessionID $e) {
            PHPUnit::assertStringContainsString('id2', $e->getMessage());
        }
    }
    
    /** @test */
    public function last_activity_is_stored_correctly()
    {
        $driver = $this->createDriver($clock = new TestClock(new DateTimeImmutable('2000-01-01')));
        
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], $clock->currentTimestamp())
        );
        
        $data = $driver->read('session1');
        
        PHPUnit::assertSame($clock->currentTimestamp(), $data->lastActivity()->getTimestamp());
    }
    
    /** @test */
    public function data_can_be_read_from_the_session()
    {
        $driver = $this->createDriver(new TestClock());
        
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], time())
        );
        
        $data = $driver->read('session1');
        PHPUnit::assertInstanceOf(SerializedSessionData::class, $data);
        
        PHPUnit::assertSame(['foo' => 'bar'], $data->asArray());
    }
    
    /** @test */
    public function data_can_be_written_to_the_session()
    {
        $driver = $this->createDriver(new TestClock());
        
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], time())
        );
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'baz'], time())
        );
        
        PHPUnit::assertSame(['foo' => 'baz'], $driver->read('session1')->asArray());
    }
    
    /** @test */
    public function a_session_can_be_destroyed()
    {
        $driver = $this->createDriver(new TestClock());
        
        $driver->write('session1', SerializedSessionData::fromArray([], time()));
        
        $driver->destroy(['session1']);
        
        try {
            $driver->read('session1');
            PHPUnit::fail("A session should not be readable after being destroyed.");
        } catch (BadSessionID $e) {
            PHPUnit::assertStringContainsString('session1', $e->getMessage());
        }
    }
    
    /** @test */
    public function multiple_session_ids_can_be_destroyed()
    {
        $driver = $this->createDriver(new TestClock());
        
        $driver->write('session1', SerializedSessionData::fromArray([], time()));
        $driver->write('session2', SerializedSessionData::fromArray([], time()));
        
        $driver->destroy(['session1', 'session2']);
        
        try {
            $driver->read('session1');
            PHPUnit::fail('Session [session1] should not have been read.');
        } catch (BadSessionID $e) {
            PHPUnit::assertStringContainsString('session1', $e->getMessage());
        }
        try {
            $driver->read('session2');
            PHPUnit::fail('Session [session2] should not have been read.');
        } catch (BadSessionID $e) {
            PHPUnit::assertStringContainsString('session2', $e->getMessage());
        }
    }
    
    /** @test */
    public function garbage_collection_works_for_old_sessions()
    {
        $driver = $this->createDriver($clock = new TestClock());
        
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], $clock->currentTimestamp())
        );
        $driver->gc(10);
        PHPUnit::assertSame(['foo' => 'bar'], $driver->read('session1')->asArray());
        
        $clock->travelIntoFuture(1);
        
        $driver->write(
            'session2',
            SerializedSessionData::fromArray(
                ['foo' => 'baz'],
                $clock->currentTimestamp()
            )
        );
        
        $clock->travelIntoFuture(10);
        
        $driver->gc(10);
        
        PHPUnit::assertSame(['foo' => 'baz'], $driver->read('session2')->asArray());
        
        try {
            $driver->read('session1');
            PHPUnit::fail("Session1 should have been garbage collected.");
        } catch (BadSessionID $e) {
            PHPUnit::assertStringContainsString('session1', $e->getMessage());
        }
    }
    
    /** @test */
    public function touching_the_session_activity_works()
    {
        $driver = $this->createDriver($clock = new TestClock());
        $driver->write(
            'session1',
            SerializedSessionData::fromArray(['foo' => 'bar'], $clock->currentTimestamp())
        );
        
        PHPUnit::assertSame(
            $clock->currentTimestamp(),
            $driver->read('session1')->lastActivity()->getTimestamp()
        );
        
        $driver->touch(
            'session1',
            (new DateTimeImmutable())->setTimestamp($clock->currentTimestamp() + 1)
        );
        
        $data = $driver->read('session1');
        
        PHPUnit::assertSame(
            $clock->currentTimestamp() + 1,
            $data->lastActivity()->getTimestamp(),
            "session was not touched correctly."
        );
        
        PHPUnit::assertSame(
            ['foo' => 'bar'],
            $data->asArray(),
            "touching the session should not change the content."
        );
    }
    
    abstract protected function createDriver(Clock $clock) :SessionDriver;
    
}