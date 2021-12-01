<?php

declare(strict_types=1);

namespace Tests\DefuseEncryption\unit;

use Snicco\Shared\Encryptor;
use Tests\Codeception\shared\UnitTest;
use Snicco\DefuseEncryption\DefuseEncryptor;
use Defuse\Crypto\Exception\BadFormatException;

class DefuseEncryptorTest extends UnitTest
{
    
    const test_key = 'def00000eab6c62bbb146de565411185853208ff7f122c72425080579256f486e6a8a28e32d33173c7b1aca3712b08c383e90c9a7e705db4fe22a49c572f7c833aa9bf31';
    
    /** @test */
    public function testConstructWithValidKey()
    {
        $encryptor = new DefuseEncryptor(self::test_key);
        
        $this->assertInstanceOf(Encryptor::class, $encryptor);
    }
    
    /** @test */
    public function testExceptionForBadKey()
    {
        $this->expectException(BadFormatException::class);
        $encryptor = new DefuseEncryptor('foobar');
    }
    
    /** @test */
    public function testEncryption()
    {
        $encryptor = new DefuseEncryptor(self::test_key);
        
        $ciphertext = $encryptor->encrypt('foobar');
        $this->assertNotSame($ciphertext, 'foobar');
        
        $plaintext = $encryptor->decrypt($ciphertext);
        $this->assertSame('foobar', $plaintext);
    }
    
}
