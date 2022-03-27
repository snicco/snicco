<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl;

use RuntimeException;

use function hash_hmac;

final class HMAC
{
    private Secret $secret;

    private string $hash_algo;

    public function __construct(Secret $secret, string $hash_algo = 'sha256')
    {
        $this->secret = $secret;
        $this->hash_algo = $hash_algo;
    }

    /**
     * @interal
     *
     * @psalm-internal Snicco\Component\SignedUrl
     */
    public function create(string $plain_text): string
    {
        $hashed = hash_hmac($this->hash_algo, $plain_text, $this->secret->asBytes(), true);
        if (false === $hashed) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not generate a hash.');
            // @codeCoverageIgnoreEnd
        }

        return $hashed;
    }
}
