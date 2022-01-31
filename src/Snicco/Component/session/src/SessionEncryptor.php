<?php

declare(strict_types=1);

namespace Snicco\Component\Session;

/**
 * @api
 */
interface SessionEncryptor
{

    public function encrypt(string $data): string;

    public function decrypt(string $data): string;

}