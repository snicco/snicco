<?php

declare(strict_types=1);

namespace Tests\unit\Encryption;

use Tests\UnitTest;
use Snicco\Session\IlluminateEncryptor;
use Snicco\Contracts\Encryptor;

class EncryptorTest extends UnitTest
{
    
    const test_key = 'base64:yRYtcDAkaEYSR2T3qaYunXW+rxD6OgIWOdSVc34Hxdw=';
    
    /** @test */
    public function a_valid_encryptor_instance_can_be_created_with_base64_encoding()
    {
        $encryptor = new IlluminateEncryptor(self::test_key);
        
        $this->assertInstanceOf(Encryptor::class, $encryptor);
    }
    
    /** @test */
    public function an_encryptor_can_be_created_without_base64_encoding()
    {
        $encryptor = new IlluminateEncryptor(str_repeat('a', 32));
        
        $encrypted = $encryptor->encrypt('foo');
        
        $this->assertNotSame('foo', $encrypted);
        
        $this->assertSame('foo', $encryptor->decrypt($encrypted));
    }
    
}
