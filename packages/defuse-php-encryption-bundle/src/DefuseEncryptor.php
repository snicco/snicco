<?php

declare(strict_types=1);

namespace Snicco\DefuseEncryption;

use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;
use Snicco\Shared\Encryptor;
use Defuse\Crypto\Exception\CryptoException;
use Snicco\Shared\Exceptions\EncryptException;

final class DefuseEncryptor implements Encryptor
{
    
    private Key $key;
    
    public function __construct(string $key_ascii)
    {
        $this->key = Key::loadFromAsciiSafeString($key_ascii);
    }
    
    public function encrypt(string $plaintext) :string
    {
        try {
            return Crypto::encrypt($plaintext, $this->key);
        } catch (CryptoException $exception) {
            throw new EncryptException(
                sprintf(
                    "Encryption failure. Caused by: %s",
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }
    }
    
    public function decrypt(string $ciphertext) :string
    {
        try {
            return Crypto::decrypt($ciphertext, $this->key);
        } catch (CryptoException $exception) {
            throw new EncryptException(
                sprintf(
                    'Decryption failure. Caused by: %s',
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }
    }
    
}