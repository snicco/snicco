<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session;

use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Component\Session\SessionEncryptor;

final class DefuseSessionEncryptor implements SessionEncryptor
{

    private DefuseEncryptor $defuse;

    public function __construct(DefuseEncryptor $defuse)
    {
        $this->defuse = $defuse;
    }

    public function encrypt(string $data): string
    {
        return $this->defuse->encrypt($data);
    }

    public function decrypt(string $data): string
    {
        return $this->defuse->decrypt($data);
    }
}