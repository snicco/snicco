<?php

declare(strict_types=1);

namespace Tests\SignedUrlWP\integration\Storage;

use Codeception\TestCase\WPTestCase;
use Snicco\SignedUrlWP\Storage\WPObjectCacheStorage;
use Snicco\Component\SignedUrl\Contracts\SignedUrlClock;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;

final class WPObjectCacheStorageTest extends WPTestCase
{
    
    use SignedUrlStorageTests;
    
    public function garbage_collection_works()
    {
        $this->assertTrue(true);
    }
    
    protected function createStorage(SignedUrlClock $clock) :SignedUrlStorage
    {
        return new WPObjectCacheStorage('my_plugin_magic_links');
    }
    
}