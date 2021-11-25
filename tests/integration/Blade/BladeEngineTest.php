<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Tests\stubs\TestApp;
use Snicco\Blade\BladeView;
use Snicco\View\Contracts\ViewFactoryInterface;
use Snicco\ExceptionHandling\Exceptions\ViewNotFoundException;

class BladeEngineTest extends BladeTestCase
{
    
    /** @test */
    public function the_blade_factory_can_create_a_blade_view()
    {
        $this->bootApp();
        
        /** @var ViewFactoryInterface $view_service */
        $view_service = TestApp::resolve(ViewFactoryInterface::class);
        
        $this->assertInstanceOf(BladeView::class, $view_service->make('foo'));
    }
    
    /** @test */
    public function exceptions_get_caught_and_translated()
    {
        $this->bootApp();
        
        /** @var ViewFactoryInterface $view_service */
        $view_service = TestApp::resolve(ViewFactoryInterface::class);
        
        $this->expectException(ViewNotFoundException::class);
        
        $this->assertInstanceOf(BladeView::class, $view_service->make('bogus'));
    }
    
}