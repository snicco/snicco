<?php

declare(strict_types=1);

namespace Snicco\Bundle\Encryption\Tests;

use Defuse\Crypto\Key;
use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Encryption\DefuseEncryptor;

/**
 * @internal
 */
final class DefuseEncryptorTest extends TestCase
{
    /**
     * @test
     */
    public function test_random_key(): void
    {
        $key = DefuseEncryptor::randomAsciiKey();

        $encryptor = new DefuseEncryptor(Key::loadFromAsciiSafeString($key));

        $ciphertext = $encryptor->encrypt('foo');
        $this->assertNotSame('foo', $ciphertext);

        $plaintext = $encryptor->decrypt($ciphertext);
        $this->assertSame('foo', $plaintext);
    }

    /**
     * @test
     */
    public function test_with_binary_inputs(): void
    {
        $key = DefuseEncryptor::randomAsciiKey();
        $encryptor = new DefuseEncryptor(Key::loadFromAsciiSafeString($key));

        $ciphertext = $encryptor->encrypt('foo', true);
        $this->assertNotSame('foo', $ciphertext);

        $plaintext = $encryptor->decrypt($ciphertext, true);
        $this->assertSame('foo', $plaintext);
    }
}
