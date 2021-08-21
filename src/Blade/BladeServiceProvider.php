<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Snicco\Contracts\ServiceProvider;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\ViewServiceProvider;
use Snicco\Contracts\ViewEngineInterface;
use Snicco\Traits\ReliesOnIlluminateContainer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Container\Container as IlluminateContainerInterface;

class BladeServiceProvider extends ServiceProvider
{
    
    use ReliesOnIlluminateContainer;
    
    public function register() :void
    {
        
        $container = $this->parseIlluminateContainer();
        
        $cache_dir = $this->config->get(
            'view.blade_cache',
            $this->app->storagePath("framework".DIRECTORY_SEPARATOR.'views')
        );
        
        $this->config->set('view.compiled', $cache_dir);
        
        $this->setIlluminateBindings($container);
        $this->registerLaravelProvider($container);
        
        $this->registerBladeViewEngine();
        
        $this->setBladeComponentBindings($container);
        
    }
    
    public function bootstrap() :void
    {
        //
    }
    
    private function setIlluminateBindings(IlluminateContainerInterface $container)
    {
        
        $container->bindIf('files', fn() => new Filesystem(), true);
        $container->bindIf('events', fn() => new Dispatcher(), true);
        
        $container->instance('config', $this->config);
        
        $this->setFacadeContainer($container);
        $this->setGlobalContainerInstance($container);
        
    }
    
    private function registerLaravelProvider(IlluminateContainerInterface $container)
    {
        
        ((new ViewServiceProvider($container)))->register();
        
    }
    
    private function registerBladeViewEngine() :void
    {
        
        $this->container->singleton(
            ViewEngineInterface::class,
            fn() => new BladeEngine($this->container->make('view'))
        );
    }
    
    private function setBladeComponentBindings(IlluminateContainerInterface $container)
    {
        
        $container->bindIf(Factory::class, fn(IlluminateContainerInterface $c) => $c->make('view'));
        $container->bindIf(Application::class, fn() => new DummyApplication());
        
        $container->resolving(
            BladeComponent::class,
            function (BladeComponent $component, IlluminateContainerInterface $container) {
                
                $component->setEngine($container->make(ViewEngineInterface::class));
                
            }
        );
        
    }
    
}