<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Storage;

use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\SignedUrl;

interface SignedUrlStorage
{
    /**
     * Decrement the number of left usages for the signed url by one.
     * Remove the signed url from storage if the new left usage is zero.
     *
     * @throws UnavailableStorage If the usage count can not be updated.
     * @throws BadIdentifier If the identifier does not exist.
     */
    public function consume(string $identifier): void;

    /**
     * @throws UnavailableStorage If the signed url can't be stored.
     */
    public function store(SignedUrl $signed_url): void;

    /**
     * Remove all expired signed links.
     *
     * @throws UnavailableStorage If garbage collection fails.
     */
    public function gc(): void;
}
