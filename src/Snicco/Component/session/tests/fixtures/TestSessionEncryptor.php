<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\fixtures;

use Snicco\Component\Session\SessionEncryptor;

class TestSessionEncryptor implements SessionEncryptor
{

    public function decrypt(string $data): string
    {
        return trim($data, 'X');
    }

    public function encrypt(string $data): string
    {
        return 'XXX' . $data . 'XXX';
    }

}