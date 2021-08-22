<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use Snicco\ExceptionHandling\Exceptions\DecryptException;
use Snicco\ExceptionHandling\Exceptions\EncryptException;

interface EncryptorInterface
{
    
    /**
     * Encrypt the given value.
     *
     * @param  mixed  $value
     * @param  bool  $serialize
     *
     * @return string
     * @throws EncryptException
     */
    public function encrypt($value, bool $serialize = false) :string;
    
    /**
     * Decrypt the given value.
     *
     * @param  string  $payload
     * @param  bool  $unserialize
     *
     * @return mixed
     * @throws DecryptException
     */
    public function decrypt(string $payload, bool $unserialize = false);
    
    /**
     * Encrypt a string without serialization.
     *
     * @param  string  $value
     *
     * @return string
     * @throws EncryptException
     */
    public function encryptString(string $value) :string;
    
    /**
     * Decrypt the given string without unserialization.
     *
     * @param  string  $payload
     *
     * @return string
     * @throws DecryptException
     */
    public function decryptString(string $payload) :string;
    
}