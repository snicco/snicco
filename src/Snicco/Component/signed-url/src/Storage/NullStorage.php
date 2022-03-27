<?php

declare(strict_types=1);


namespace Snicco\Component\SignedUrl\Storage;

use Snicco\Component\SignedUrl\SignedUrl;

/**
 * The NullStorage can be used if you want to verify your signed urls
 * purely based on signature and expiration.
 * You will not be able to enforce a usage limit.
 *
 * @codeCoverageIgnore
 */
final class NullStorage implements SignedUrlStorage
{

    public function consume(string $identifier): void
    {
        //
    }

    public function store(SignedUrl $signed_url): void
    {
        //
    }

    public function gc(): void
    {
        //
    }
}