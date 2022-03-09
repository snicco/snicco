<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Snicco\Component\SignedUrl\Storage\InMemoryStorage;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;
use Snicco\Component\TestableClock\Clock;

/**
 * @internal
 */
final class InMemoryStorageTest extends TestCase
{
    use SignedUrlStorageTests;

    protected function createStorage(Clock $clock): SignedUrlStorage
    {
        return new InMemoryStorage($clock);
    }
}
