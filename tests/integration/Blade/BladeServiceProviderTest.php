<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Illuminate\View\Factory;
use Snicco\Blade\BladeViewFactory;
use Illuminate\View\FileViewFinder;
use Illuminate\Container\Container;
use Snicco\View\Contracts\ViewFactory;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;

class BladeServiceProviderTest extends BladeTestCase
{
    
    /** @test */
    public function the_blade_view_factory_is_bound_correctly()
    {
        $this->bootApp();
        $container = Container::getInstance();
        $this->assertInstanceOf(Factory::class, $container->make('view'));
    }
    
    /** @test */
    public function the_blade_view_finder_is_bound_correctly()
    {
        $this->bootApp();
        $container = Container::getInstance();
        $this->assertInstanceOf(FileViewFinder::class, $container->make('view.finder'));
    }
    
    /** @test */
    public function the_blade_compiler_is_bound_correctly()
    {
        $this->bootApp();
        $container = Container::getInstance();
        $this->assertInstanceOf(BladeCompiler::class, $container->make('blade.compiler'));
    }
    
    /** @test */
    public function the_engine_resolver_is_bound_correctly()
    {
        $this->bootApp();
        $container = Container::getInstance();
        $this->assertInstanceOf(EngineResolver::class, $container->make('view.engine.resolver'));
    }
    
    /** @test */
    public function the_view_service_now_uses_the_blade_engine()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            BladeViewFactory::class,
            $this->app->resolve(ViewFactory::class)
        );
    }
    
    /** @test */
    public function a_custom_view_cache_path_can_be_provided()
    {
        $this->withAddedConfig('view.blade_cache', __DIR__)->bootApp();
        
        $this->assertSame(__DIR__, Container::getInstance()['config']['view.compiled']);
    }
    
}