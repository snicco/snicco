<?php

declare(strict_types=1);

namespace Tests\Core\integration\Routing;

use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class RouteCachingTest extends FrameworkTestCase
{
    
    /** @test */
    public function an_exception_is_thrown_for_invalid_cache_dirs()
    {
        $this->expectException(ConfigurationException::class);
        
        $this->withAddedConfig('routing.cache', true);
        $this->withAddedConfig('routing.cache_dir', 'bogus');
        $this->withRequest($this->frontendRequest('GET', '/foo'));
        $this->bootApp();
    }
    
    /** @test */
    public function the_cache_dir_will_be_created_if_it_doesnt_exist()
    {
        $this->assertFalse(is_dir($dir = TEST_APP_BASE_PATH.'/storage/framework/routes'));
        
        $this->withAddedConfig('routing.cache', true);
        $this->withRequest($this->frontendRequest('GET', '/foo'));
        
        $this->bootApp();
        
        $this->assertTrue(is_dir($dir));
        
        if (is_dir($dir)) {
            $this->rmdir($dir);
            rmdir($dir);
        }
    }
    
}