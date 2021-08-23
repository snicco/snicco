<?php

namespace Tests\integration\Application;

use Tests\FrameworkTestCase;

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
    
}