<?php

declare(strict_types=1);


namespace Snicco\Bundle\Encryption;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

final class DefuseEncryptor
{
    private Key $key;

    public function __construct(Key $key)
    {
        $this->key = $key;
    }

    public function encrypt(string $plain_text, bool $return_as_binary = false): string
    {
        return Crypto::encrypt($plain_text, $this->key, $return_as_binary);
    }

    public function decrypt(string $ciphertext, bool $is_binary = false): string
    {
        return Crypto::decrypt($ciphertext, $this->key, $is_binary);
    }

    public static function randomAsciiKey(): string
    {
        return Key::createNewRandomKey()->saveToAsciiSafeString();
    }
}
