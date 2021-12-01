<?php

declare(strict_types=1);

namespace Snicco\Shared;

use Snicco\Shared\Exceptions\EncryptException;

interface Encryptor
{
    
    /**
     * @param  string  $plaintext
     *
     * @return string
     * @throws EncryptException
     */
    public function encrypt(string $plaintext) :string;
    
    /**
     * @param  string  $ciphertext
     *
     * @return string
     * @throws EncryptException
     */
    public function decrypt(string $ciphertext) :string;
    
}