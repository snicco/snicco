<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Snicco\Blade\BladeView;
use Snicco\View\ViewEngine;
use Snicco\View\Exceptions\ViewNotFoundException;

class BladeEngineTest extends BladeTestCase
{
    
    /** @test */
    public function the_blade_factory_can_create_a_blade_view()
    {
        $this->bootApp();
        
        /** @var ViewEngine $view_service */
        $view_service = $this->app->resolve(ViewEngine::class);
        
        $this->assertInstanceOf(BladeView::class, $view_service->make('foo'));
    }
    
    /** @test */
    public function exceptions_get_caught_and_translated()
    {
        $this->bootApp();
        
        /** @var ViewEngine $view_factory */
        $view_factory = $this->app->resolve(ViewEngine::class);
        
        $this->expectException(ViewNotFoundException::class);
        
        $this->assertInstanceOf(BladeView::class, $view_factory->make('bogus'));
    }
    
}