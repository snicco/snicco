<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Closure;
use Illuminate\Contracts\Foundation\Application;

/**
 * Blade is not 100% decoupled from laravel and in some rare cases
 * relies on an Application instance being present.
 * This class fulfills that role. When rendering BladeComponents the getNamespace method will be
 * called where have to return an empty string.
 *
 * @codeCoverageIgnore
 * @internal
 */
class DummyApplication implements Application
{
    
    public function getNamespace()
    {
        return '';
    }
    
    public function version()
    {
    }
    
    public function basePath($path = '')
    {
    }
    
    public function bootstrapPath($path = '')
    {
    }
    
    public function configPath($path = '')
    {
    }
    
    public function databasePath($path = '')
    {
    }
    
    public function resourcePath($path = '')
    {
    }
    
    public function storagePath()
    {
    }
    
    public function environment(...$environments)
    {
    }
    
    public function runningInConsole()
    {
    }
    
    public function runningUnitTests()
    {
    }
    
    public function isDownForMaintenance()
    {
    }
    
    public function registerConfiguredProviders()
    {
    }
    
    public function register($provider, $force = false)
    {
    }
    
    public function registerDeferredProvider($provider, $service = null)
    {
    }
    
    public function resolveProvider($provider)
    {
    }
    
    public function boot()
    {
    }
    
    public function booting($callback)
    {
    }
    
    public function booted($callback)
    {
    }
    
    public function bootstrapWith(array $bootstrappers)
    {
    }
    
    public function getLocale()
    {
    }
    
    public function getProviders($provider)
    {
    }
    
    public function hasBeenBootstrapped()
    {
    }
    
    public function loadDeferredProviders()
    {
    }
    
    public function setLocale($locale)
    {
    }
    
    public function shouldSkipMiddleware()
    {
    }
    
    public function terminate()
    {
    }
    
    public function bound($abstract)
    {
    }
    
    public function alias($abstract, $alias)
    {
    }
    
    public function tag($abstracts, $tags)
    {
    }
    
    public function tagged($tag)
    {
    }
    
    public function bind($abstract, $concrete = null, $shared = false)
    {
    }
    
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
    }
    
    public function singleton($abstract, $concrete = null)
    {
    }
    
    public function singletonIf($abstract, $concrete = null)
    {
    }
    
    public function extend($abstract, Closure $closure)
    {
    }
    
    public function instance($abstract, $instance)
    {
    }
    
    public function addContextualBinding($concrete, $abstract, $implementation)
    {
    }
    
    public function when($concrete)
    {
    }
    
    public function factory($abstract)
    {
    }
    
    public function flush()
    {
    }
    
    public function make($abstract, array $parameters = [])
    {
    }
    
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
    }
    
    public function resolved($abstract)
    {
    }
    
    public function resolving($abstract, Closure $callback = null)
    {
    }
    
    public function afterResolving($abstract, Closure $callback = null)
    {
    }
    
    public function get(string $id)
    {
    }
    
    public function has(string $id)
    {
    }
    
}