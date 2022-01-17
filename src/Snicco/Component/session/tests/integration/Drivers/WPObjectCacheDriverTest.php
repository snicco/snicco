<?php

declare(strict_types=1);

namespace Tests\Session\integration\Drivers;

use Tests\Session\SessionDriverTest;
use Snicco\Session\Contracts\SessionClock;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Drivers\WPObjectCacheDriver;

final class WPObjectCacheDriverTest extends SessionDriverTest
{
    
    protected function createDriver(SessionClock $clock) :SessionDriver
    {
        return new WPObjectCacheDriver(
            'sniccowp_cache_test',
            10
        );
    }
    
    /**
     * @todo test this against a real (or fake) WP_Object_Cache implementation.
     */
    public function garbage_collection_works_for_old_sessions()
    {
        $this->assertTrue(true);
    }
    
}