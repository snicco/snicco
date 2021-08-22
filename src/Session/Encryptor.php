<?php

declare(strict_types=1);

namespace Snicco\Session;

use Snicco\Support\Str;
use Illuminate\Encryption\Encrypter;
use Snicco\Contracts\EncryptorInterface;
use Snicco\ExceptionHandling\Exceptions\EncryptException;
use Snicco\ExceptionHandling\Exceptions\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException as IlluminateEncryptException;
use Illuminate\Contracts\Encryption\DecryptException as IlluminateDecryptException;

class Encryptor implements EncryptorInterface
{
    
    private Encrypter $encryptor;
    
    public function __construct(string $key)
    {
        $this->encryptor = new Encrypter($this->parseKey($key), 'AES-256-CBC');
    }
    
    public function encrypt($value, bool $serialize = false) :string
    {
        
        try {
            
            return $this->encryptor->encrypt($value, $serialize);
            
        } catch (IlluminateEncryptException $e) {
            
            throw new EncryptException($e->getMessage(), $e);
            
        }
        
    }
    
    public function decrypt(string $payload, bool $unserialize = false)
    {
        
        try {
            
            return $this->encryptor->decrypt($payload, $unserialize);
            
        } catch (IlluminateDecryptException $e) {
            
            throw new DecryptException($e->getMessage(), $e);
            
        }
        
    }
    
    public function encryptString(string $value) :string
    {
        
        try {
            
            return $this->encryptor->encrypt($value, false);
            
        } catch (IlluminateEncryptException $e) {
            
            throw new EncryptException($e->getMessage());
            
        }
        
    }
    
    public function decryptString(string $payload) :string
    {
        
        try {
            
            return $this->encryptor->decrypt($payload, false);
            
        } catch (IlluminateDecryptException $e) {
            
            throw new DecryptException($e->getMessage());
            
        }
        
    }
    
    private function parseKey(string $key) :string
    {
        
        if (Str::startsWith($key, $prefix = 'base64:')) {
            
            $key = base64_decode(Str::after($key, $prefix));
            
        }
        
        return $key;
    }
    
}