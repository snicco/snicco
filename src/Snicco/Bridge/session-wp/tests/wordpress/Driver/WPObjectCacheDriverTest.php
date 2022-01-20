<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionWP\Tests\wordpress\Driver;

use Snicco\Component\Session\SessionClock;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Bridge\SessionWP\Driver\WPObjectCacheDriver;
use Snicco\Bridge\SessionWP\Tests\wordpress\WPTestCase;

final class WPObjectCacheDriverTest extends WPTestCase
{
    
    /**
     * @todo test this against a real (or fake) WP_Object_Cache implementation.
     */
    public function garbage_collection_works_for_old_sessions()
    {
        $this->assertTrue(true);
    }
    
    protected function createDriver(SessionClock $clock) :SessionDriver
    {
        return new WPObjectCacheDriver(
            'sniccowp_cache_test',
            10
        );
    }
    
}