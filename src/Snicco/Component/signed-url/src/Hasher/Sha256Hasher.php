<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Hasher;

use RuntimeException;

use function hash_hmac;

final class Sha256Hasher extends Hasher
{

    public function hash(string $plain_text): string
    {
        $hashed = hash_hmac('sha256', $plain_text, $this->secret->asBytes(), true);
        if (false === $hashed) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not generate a hash.');
            // @codeCoverageIgnoreEnd
        }
        return $hashed;
    }

}