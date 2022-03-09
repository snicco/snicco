<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\Drivers;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\Driver\EncryptedDriver;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Driver\UserSessionsDriver;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\Session\Testing\UserSessionDriverTests;
use Snicco\Component\Session\Tests\fixtures\TestSessionEncryptor;
use Snicco\Component\Session\ValueObject\SerializedSession;
use Snicco\Component\TestableClock\Clock;

use function time;

final class EncryptedDriverTest extends TestCase
{
    use SessionDriverTests;
    use UserSessionDriverTests;

    /**
     * @test
     */
    public function session_content_is_encrypted(): void
    {
        $array_driver = new InMemoryDriver();

        $driver = new EncryptedDriver(
            $array_driver,
            new TestSessionEncryptor()
        );

        $driver->write(
            'session1',
            SerializedSession::fromString('foo_data', 'validator', time())
        );

        $all = $array_driver->all();

        $this->assertArrayHasKey('session1', $all);

        $session_in_inner_driver = $array_driver->read('session1');
        $this->assertNotSame('foo_data', $session_in_inner_driver->data());

        $this->assertSame('foo_data', $driver->read('session1')->data());
    }

    /**
     * @test
     */
    public function test_destroyAll_throws_exception(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('destroyAll');

        $driver = new EncryptedDriver(
            $this->notUserSessionDriver(),
            new TestSessionEncryptor()
        );
        $driver->destroyAll();
    }

    /**
     * @test
     */
    public function test_destroyAllForUserId_throws_exception(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('destroyAllForUserId');

        $driver = new EncryptedDriver(
            $this->notUserSessionDriver(),
            new TestSessionEncryptor()
        );
        $driver->destroyAllForUserId(1);
    }

    /**
     * @test
     */
    public function test_getAllForUserId_throws_exception(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('getAllForUserId');

        $driver = new EncryptedDriver(
            $this->notUserSessionDriver(),
            new TestSessionEncryptor()
        );
        $driver->getAllForUserId(1);
    }

    /**
     * @test
     */
    public function test_destroyAllForUserIdExcept_throws_exception(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('destroyAllForUserIdExcept');

        $driver = new EncryptedDriver(
            $this->notUserSessionDriver(),
            new TestSessionEncryptor()
        );
        $driver->destroyAllForUserIdExcept('s', 1);
    }

    protected function createDriver(Clock $clock): SessionDriver
    {
        return new EncryptedDriver(
            new InMemoryDriver($clock),
            new TestSessionEncryptor()
        );
    }

    protected function createUserSessionDriver(array $user_sessions): UserSessionsDriver
    {
        $array_driver = new InMemoryDriver();

        $driver = new EncryptedDriver(
            $array_driver,
            new TestSessionEncryptor()
        );

        foreach ($user_sessions as $selector => $user_session) {
            $array_driver->write($selector, $user_session);
        }
        return $driver;
    }

    private function notUserSessionDriver(): SessionDriver
    {
        return new class() implements SessionDriver {
            public function read(string $selector): SerializedSession
            {
                throw new BadMethodCallException(__METHOD__);
            }

            public function write(string $selector, SerializedSession $session): void
            {
                throw new BadMethodCallException(__METHOD__);
            }

            public function destroy(array $selectors): void
            {
                throw new BadMethodCallException(__METHOD__);
            }

            public function gc(int $seconds_without_activity): void
            {
                throw new BadMethodCallException(__METHOD__);
            }

            public function touch(string $selector, int $current_timestamp): void
            {
                throw new BadMethodCallException(__METHOD__);
            }
        };
    }
}
