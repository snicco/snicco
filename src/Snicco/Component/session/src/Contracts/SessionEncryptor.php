<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

/**
 * @api
 */
interface SessionEncryptor
{
    
    public function encrypt(string $data) :string;
    
    public function decrypt(string $data) :string;
    
}