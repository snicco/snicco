<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Tests\stubs\TestApp;
use Illuminate\View\Factory;
use Snicco\Blade\BladeEngine;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;
use Snicco\View\Contracts\ViewEngineInterface;

class BladeServiceProviderTest extends BladeTestCase
{
    
    /** @test */
    public function the_blade_view_factory_is_bound_correctly()
    {
        $this->bootApp();
        $this->assertInstanceOf(Factory::class, TestApp::resolve('view'));
    }
    
    /** @test */
    public function the_blade_view_finder_is_bound_correctly()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(FileViewFinder::class, TestApp::resolve('view.finder'));
    }
    
    /** @test */
    public function the_blade_compiler_is_bound_correctly()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(BladeCompiler::class, TestApp::resolve('blade.compiler'));
    }
    
    /** @test */
    public function the_engine_resolver_is_bound_correctly()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(EngineResolver::class, TestApp::resolve('view.engine.resolver'));
    }
    
    /** @test */
    public function the_view_service_now_uses_the_blade_engine()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(BladeEngine::class, TestApp::resolve(ViewEngineInterface::class));
    }
    
    /** @test */
    public function a_custom_view_cache_path_can_be_provided()
    {
        $this->withAddedConfig('view.blade_cache', __DIR__)->bootApp();
        
        $this->assertSame(__DIR__, TestApp::config('view.compiled'));
    }
    
}