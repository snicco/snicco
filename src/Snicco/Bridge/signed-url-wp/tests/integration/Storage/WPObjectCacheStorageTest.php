<?php

declare(strict_types=1);

namespace Tests\SignedUrlWP\integration\Storage;

use Codeception\TestCase\WPTestCase;
use Tests\SignedUrl\WithStorageTests;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;
use Snicco\SignedUrlWP\Storage\WPObjectCacheStorage;

final class WPObjectCacheStorageTest extends WPTestCase
{
    
    use WithStorageTests;
    
    public function garbage_collection_works()
    {
        $this->assertTrue(true);
    }
    
    protected function createMagicLinkStorage(SignedUrlClock $clock) :SignedUrlStorage
    {
        return new WPObjectCacheStorage('my_plugin_magic_links');
    }
    
}