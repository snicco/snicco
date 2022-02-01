<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\Drivers;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\Driver\EncryptedDriver;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\Session\Tests\fixtures\TestSessionEncryptor;
use Snicco\Component\Session\ValueObject\SerializedSessionData;
use Snicco\Component\TestableClock\Clock;

final class EncryptedDriverTest extends TestCase
{

    use SessionDriverTests;

    /** @test */
    public function session_content_is_encrypted()
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

    protected function createDriver(Clock $clock): SessionDriver
    {
        return new EncryptedDriver(
            new InMemoryDriver($clock),
            new TestSessionEncryptor()
        );
    }

}
