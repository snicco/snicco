<?php

declare(strict_types=1);

namespace Snicco\Session;

use Snicco\Shared\Encryptor;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\ExceptionHandling\Exceptions\EncryptException;

class EncryptedSession extends Session
{
    
    protected Encryptor $encryptor;
    
    public function __construct(SessionDriver $handler, Encryptor $encryptor, int $strength = 32)
    {
        $this->encryptor = $encryptor;
        
        parent::__construct($handler, $strength);
    }
    
    /**
     * Prepare the raw string data from the session for unserialization.
     *
     * @param  string  $data
     *
     * @return string
     */
    protected function prepareForUnserialize(string $data) :string
    {
        return $this->encryptor->decrypt($data);
    }
    
    /**
     * Prepare the serialized session data for storage.
     *
     * @param  string  $data
     *
     * @return string
     * @throws EncryptException
     */
    protected function prepareForStorage(string $data) :string
    {
        return $this->encryptor->encrypt($data);
    }
    
}