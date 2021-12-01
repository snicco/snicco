<?php

namespace Tests\Core\integration\Application;

use Tests\Codeception\shared\FrameworkTestCase;

class ApplicationTest extends FrameworkTestCase
{
    
    /** @test */
    public function initial_attributes_on_the_app_config_will_not_be_overwritten_during_booting()
    {
        $app = $this->app;
        $app->config()->set('app.editor', 'vsc');
        $app->boot();
        
        $this->assertSame('vsc', $app->config('app.editor'));
    }
    
    /** @test */
    public function testEnvironmentUpdatedInConfig()
    {
        $_SERVER['argv'][] = '--env';
        $_SERVER['argv'][] = 'production';
        
        $this->bootApp();
        
        $this->assertSame('production', $this->app->environment());
        $this->assertSame('production', $this->app->config('app.env'));
        
        array_pop($_SERVER['argv']);
        array_pop($_SERVER['argv']);
        $this->assertNotContains('--env', $_SERVER['argv']);
        $this->assertNotContains('production', $_SERVER['argv']);
    }
    
}
