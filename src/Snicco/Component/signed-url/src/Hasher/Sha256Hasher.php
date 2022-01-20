<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Hasher;

use function hash_hmac;

/**
 * @api
 */
final class Sha256Hasher extends Hasher
{
    
    public function hash(string $plain_text) :string
    {
        return hash_hmac('sha256', $plain_text, $this->secret->asBytes(), true);
    }
    
}