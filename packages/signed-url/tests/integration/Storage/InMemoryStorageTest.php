<?php

declare(strict_types=1);

namespace Tests\SignedUrl\integration\Storage;

use Codeception\TestCase\WPTestCase;
use Tests\SignedUrl\WithStorageTests;
use Snicco\SignedUrl\Storage\InMemoryStorage;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;

final class InMemoryStorageTest extends WPTestCase
{
    
    use WithStorageTests;
    
    protected function createMagicLinkStorage(SignedUrlClock $clock) :SignedUrlStorage
    {
        return new InMemoryStorage($clock);
    }
    
}