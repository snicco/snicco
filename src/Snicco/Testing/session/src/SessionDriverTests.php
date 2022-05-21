<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Testing;

use DateTimeImmutable;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Exception\UnknownSessionSelector;
use Snicco\Component\Session\ValueObject\SerializedSession;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;

use function time;

/**
 * @codeCoverageIgnore
 */
trait SessionDriverTests
{
    /**
     * @test
     */
    final public function read_from_session_throws_exception_for_bad_id(): void
    {
        $driver = $this->createDriver(new TestClock());

        $driver->write('id1', SerializedSession::fromString('foo', 'validator', time()), );

        try {
            $driver->read('id2');
            PHPUnit::fail('An exception should have been thrown for reading a bad session id.');
        } catch (UnknownSessionSelector $e) {
            PHPUnit::assertStringContainsString('id2', $e->getMessage());
        }
    }

    /**
     * @test
     */
    final public function last_activity_is_stored_correctly(): void
    {
        $driver = $this->createDriver($clock = new TestClock(new DateTimeImmutable('2000-01-01')));

        $driver->write('session1', SerializedSession::fromString('foo', 'validator', $clock->currentTimestamp()), );

        $serialized_session = $driver->read('session1');

        PHPUnit::assertSame($clock->currentTimestamp(), $serialized_session->lastActivity());
    }

    /**
     * @test
     */
    final public function data_can_be_read_from_the_driver(): void
    {
        $driver = $this->createDriver(new TestClock());

        $driver->write('session1', SerializedSession::fromString('foo', 'validator', time(), 1), );

        $session = $driver->read('session1');

        PHPUnit::assertSame('foo', $session->data());
        PHPUnit::assertSame('validator', $session->hashedValidator());
        PHPUnit::assertSame(1, $session->userId());
    }

    /**
     * @test
     */
    final public function data_can_be_written_to_an_existing_session(): void
    {
        $driver = $this->createDriver(new TestClock());

        $driver->write('session1', SerializedSession::fromString('foo', 'validator', time()), );

        $driver->write('session1', SerializedSession::fromString('bar', 'validator', time()), );

        PHPUnit::assertSame('bar', $driver->read('session1')->data());
    }

    /**
     * @test
     */
    final public function a_session_can_be_destroyed(): void
    {
        $driver = $this->createDriver(new TestClock());

        $driver->write('session1', SerializedSession::fromString('foo', 'validator', time()), );

        $driver->destroy('session1');

        try {
            $driver->read('session1');
            PHPUnit::fail('A session should not be readable after being destroyed.');
        } catch (UnknownSessionSelector $e) {
            PHPUnit::assertStringContainsString('session1', $e->getMessage());
        }
    }

    /**
     * @test
     */
    final public function garbage_collection_works_for_old_sessions(): void
    {
        $driver = $this->createDriver($clock = new TestClock());

        $driver->write('session1', SerializedSession::fromString('foo', 'validator', $clock->currentTimestamp()), );

        $driver->gc($this->idleTimeout());

        PHPUnit::assertSame('foo', $driver->read('session1')->data());

        $this->travelIntoFuture($clock, 1);

        $driver->write(
            'session2',
            SerializedSession::fromString('bar', 'validator2', $clock->currentTimestamp()),
        );

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->travelIntoFuture($clock, $this->idleTimeout() - 1);

        $driver->gc($this->idleTimeout());

        // Session is still active by one second.
        PHPUnit::assertSame('bar', $driver->read('session2')->data());

        try {
            $driver->read('session1');
            PHPUnit::fail('Session1 should have been garbage collected.');
        } catch (UnknownSessionSelector $e) {
            PHPUnit::assertStringContainsString('session1', $e->getMessage());
        }
    }

    /**
     * @test
     */
    final public function touching_the_session_activity_works(): void
    {
        $driver = $this->createDriver($clock = new TestClock());

        $driver->write('session1', SerializedSession::fromString('foo', 'validator', $clock->currentTimestamp()), );

        PHPUnit::assertSame($clock->currentTimestamp(), $driver->read('session1')->lastActivity());

        $driver->touch('session1', $clock->currentTimestamp() + 1);

        $session = $driver->read('session1');

        PHPUnit::assertSame(
            $clock->currentTimestamp() + 1,
            $session->lastActivity(),
            'session was not touched correctly.'
        );

        PHPUnit::assertSame('foo', $session->data(), 'touching the session should not change the content.');
    }

    /**
     * @test
     */
    final public function test_touch_throws_exception_for_bad_id(): void
    {
        $driver = $this->createDriver($clock = new TestClock());

        $driver->write('session1', SerializedSession::fromString('foo', 'validator', $clock->currentTimestamp()), );

        $driver->touch('session1', $clock->currentTimestamp() + 1);

        $this->expectException(UnknownSessionSelector::class);

        $driver->touch('session2', $clock->currentTimestamp() + 1);
    }

    /**
     * @param 0|positive-int $seconds
     */
    protected function travelIntoFuture(TestClock $clock, int $seconds): void
    {
        $clock->travelIntoFuture($seconds);
    }

    /**
     * @return positive-int
     */
    protected function idleTimeout(): int
    {
        return 2;
    }

    abstract protected function createDriver(Clock $clock): SessionDriver;
}
