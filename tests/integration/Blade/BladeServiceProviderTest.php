<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Tests\stubs\TestApp;
use Illuminate\View\Factory;
use Snicco\Blade\BladeEngine;
use Illuminate\View\FileViewFinder;
use Snicco\Contracts\ViewEngineInterface;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;

class BladeServiceProviderTest extends BladeTestCase
{
    
    protected bool $defer_boot = true;
    
    /** @test */
    public function the_blade_view_factory_is_bound_correctly()
    {
        
        $this->boot();
        $this->assertInstanceOf(Factory::class, TestApp::resolve('view'));
        
    }
    
    /** @test */
    public function the_blade_view_finder_is_bound_correctly()
    {
        
        $this->boot();
        
        $this->assertInstanceOf(FileViewFinder::class, TestApp::resolve('view.finder'));
        
    }
    
    /** @test */
    public function the_blade_compiler_is_bound_correctly()
    {
        
        $this->boot();
        
        $this->assertInstanceOf(BladeCompiler::class, TestApp::resolve('blade.compiler'));
        
    }
    
    /** @test */
    public function the_engine_resolver_is_bound_correctly()
    {
        
        $this->boot();
        
        $this->assertInstanceOf(EngineResolver::class, TestApp::resolve('view.engine.resolver'));
        
    }
    
    /** @test */
    public function the_view_service_now_uses_the_blade_engine()
    {
        
        $this->boot();
        
        $this->assertInstanceOf(BladeEngine::class, TestApp::resolve(ViewEngineInterface::class));
        
    }
    
    /** @test */
    public function a_custom_view_cache_path_can_be_provided()
    {
        
        $this->withAddedConfig('view.blade_cache', __DIR__)->boot();
        
        $this->assertSame(__DIR__, TestApp::config('view.compiled'));
        
    }
    
}