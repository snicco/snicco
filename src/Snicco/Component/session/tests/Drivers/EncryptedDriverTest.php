<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\Drivers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Session\Driver\EncryptedDriver;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\Session\Tests\fixtures\TestSessionEncryptor;
use Snicco\Component\Session\ValueObject\SerializedSessionData;
use Snicco\Component\TestableClock\Clock;

use function time;

final class EncryptedDriverTest extends TestCase
{

    use SessionDriverTests;

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

        $driver->write('session1', SerializedSessionData::fromArray(['foo' => 'bar'], time()));

        $all = $array_driver->all();

        $this->assertArrayHasKey('session1', $all);

        $session_in_inner_driver = $array_driver->read('session1');
        $this->assertNotSame(['foo' => 'bar'], $session_in_inner_driver->asArray());

        $this->assertSame(['foo' => 'bar'], $driver->read('session1')->asArray());
    }

    /**
     * @test
     */
    public function test_exception_for_invalid_encrypted_data(): void
    {
        $array_driver = new InMemoryDriver();
        $array_driver->write('session1', SerializedSessionData::fromArray(['foo' => 'bar'], time()));

        $driver = new EncryptedDriver(
            $array_driver,
            new TestSessionEncryptor()
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The session data is corrupted. Does not contain key [encrypted_session_data].');

        $driver->read('session1');
    }

    protected function createDriver(Clock $clock): SessionDriver
    {
        return new EncryptedDriver(
            new InMemoryDriver($clock),
            new TestSessionEncryptor()
        );
    }

}
