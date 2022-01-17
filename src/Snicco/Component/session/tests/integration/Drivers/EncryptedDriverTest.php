<?php

declare(strict_types=1);

namespace Tests\Session\integration\Drivers;

use Tests\Session\SessionDriverTest;
use Snicco\Session\Contracts\SessionClock;
use Snicco\Session\Drivers\EncryptedDriver;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Session\Contracts\SessionEncryptor;
use Snicco\Session\ValueObjects\SerializedSessionData;

final class EncryptedDriverTest extends SessionDriverTest
{
    
    /** @test */
    public function session_content_is_encrypted()
    {
        $array_driver = new ArraySessionDriver();
        
        $driver = new EncryptedDriver($array_driver, new TestSessionEncryptor());
        
        $driver->write('session1', SerializedSessionData::fromArray(['foo' => 'bar'], time()));
        
        $all = $array_driver->all();
        
        $this->assertArrayHasKey('session1', $all);
        
        $session_in_inner_driver = $array_driver->read('session1');
        $this->assertNotSame(['foo' => 'bar'], $session_in_inner_driver->asArray());
        
        $this->assertSame(['foo' => 'bar'], $driver->read('session1')->asArray());
    }
    
    protected function createDriver(SessionClock $clock) :SessionDriver
    {
        return new EncryptedDriver(
            new ArraySessionDriver($clock),
            new TestSessionEncryptor()
        );
    }
    
}

class TestSessionEncryptor implements SessionEncryptor
{
    
    public function decrypt(string $data) :string
    {
        return trim($data, 'X');
    }
    
    public function encrypt(string $data) :string
    {
        return 'XXX'.$data.'XXX';
    }
    
}