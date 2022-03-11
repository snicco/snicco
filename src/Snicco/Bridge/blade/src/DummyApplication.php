<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use BadMethodCallException;
use Closure;
use Illuminate\Contracts\Foundation\Application;

/**
 * Blade is not 100% decoupled from laravel and in some rare cases relies on an
 * Application instance being present. This class fulfills that role. When
 * rendering BladeComponents the getNamespace method will be called where have
 * to return an empty string.
 *
 * @psalm-internal Snicco\Bridge\Blade
 *
 * @internal
 *
 * @codeCoverageIgnore
 */
final class DummyApplication implements Application
{
    public function getNamespace(): string
    {
        return '';
    }

    public function version()
    {
        throw new BadMethodCallException('version() is not supported for the DummyApplication');
    }

    public function basePath($path = '')
    {
        throw new BadMethodCallException('basePath() is not supported for the DummyApplication');
    }

    public function bootstrapPath($path = '')
    {
        throw new BadMethodCallException('bootstrapPath() is not supported for the DummyApplication');
    }

    public function configPath($path = '')
    {
        throw new BadMethodCallException('bootstrapPath() is not supported for the DummyApplication');
    }

    public function databasePath($path = '')
    {
        throw new BadMethodCallException('databasePath() is not supported for the DummyApplication');
    }

    public function resourcePath($path = '')
    {
        throw new BadMethodCallException('resourcePath() is not supported for the DummyApplication');
    }

    public function storagePath()
    {
        throw new BadMethodCallException('storagePath() is not supported for the DummyApplication');
    }

    public function environment(...$environments)
    {
        throw new BadMethodCallException('environment() is not supported for the DummyApplication');
    }

    public function runningInConsole()
    {
        throw new BadMethodCallException('runningInConsole() is not supported for the DummyApplication');
    }

    public function runningUnitTests()
    {
        throw new BadMethodCallException('runningUnitTests() is not supported for the DummyApplication');
    }

    public function isDownForMaintenance()
    {
        throw new BadMethodCallException('isDownForMaintenance() is not supported for the DummyApplication');
    }

    public function registerConfiguredProviders()
    {
        throw new BadMethodCallException('registerConfiguredProviders() is not supported for the DummyApplication');
    }

    public function register($provider, $force = false)
    {
        throw new BadMethodCallException('register() is not supported for the DummyApplication');
    }

    public function registerDeferredProvider($provider, $service = null)
    {
        throw new BadMethodCallException('registerDeferredProvider() is not supported for the DummyApplication');
    }

    public function resolveProvider($provider)
    {
        throw new BadMethodCallException('resolveProvider() is not supported for the DummyApplication');
    }

    public function boot()
    {
        throw new BadMethodCallException('boot() is not supported for the DummyApplication');
    }

    public function booting($callback)
    {
        throw new BadMethodCallException('booting() is not supported for the DummyApplication');
    }

    public function booted($callback)
    {
        throw new BadMethodCallException('booted() is not supported for the DummyApplication');
    }

    public function bootstrapWith(array $bootstrappers)
    {
        throw new BadMethodCallException('bootstrapWith() is not supported for the DummyApplication');
    }

    public function getLocale()
    {
        throw new BadMethodCallException('getLocale() is not supported for the DummyApplication');
    }

    public function getProviders($provider)
    {
        throw new BadMethodCallException('getProviders() is not supported for the DummyApplication');
    }

    public function hasBeenBootstrapped()
    {
        throw new BadMethodCallException('hasBeenBootstrapped() is not supported for the DummyApplication');
    }

    public function loadDeferredProviders()
    {
        throw new BadMethodCallException('loadDeferredProviders() is not supported for the DummyApplication');
    }

    public function setLocale($locale)
    {
        throw new BadMethodCallException('setLocale() is not supported for the DummyApplication');
    }

    public function shouldSkipMiddleware()
    {
        throw new BadMethodCallException('shouldSkipMiddleware() is not supported for the DummyApplication');
    }

    public function terminate()
    {
        throw new BadMethodCallException('terminate() is not supported for the DummyApplication');
    }

    public function bound($abstract)
    {
        throw new BadMethodCallException('bound() is not supported for the DummyApplication');
    }

    public function alias($abstract, $alias)
    {
        throw new BadMethodCallException('alias() is not supported for the DummyApplication');
    }

    public function tag($abstracts, $tags)
    {
        throw new BadMethodCallException('tag() is not supported for the DummyApplication');
    }

    public function tagged($tag)
    {
        throw new BadMethodCallException('tagged() is not supported for the DummyApplication');
    }

    public function bind($abstract, $concrete = null, $shared = false)
    {
        throw new BadMethodCallException('bind() is not supported for the DummyApplication');
    }

    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        throw new BadMethodCallException('bindIf() is not supported for the DummyApplication');
    }

    public function singleton($abstract, $concrete = null)
    {
        throw new BadMethodCallException('singleton() is not supported for the DummyApplication');
    }

    public function singletonIf($abstract, $concrete = null)
    {
        throw new BadMethodCallException('singletonIf() is not supported for the DummyApplication');
    }

    public function extend($abstract, Closure $closure)
    {
        throw new BadMethodCallException('extend() is not supported for the DummyApplication');
    }

    public function instance($abstract, $instance)
    {
        throw new BadMethodCallException('instance() is not supported for the DummyApplication');
    }

    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        throw new BadMethodCallException('addContextualBinding() is not supported for the DummyApplication');
    }

    public function when($concrete)
    {
        throw new BadMethodCallException('when() is not supported for the DummyApplication');
    }

    public function factory($abstract)
    {
        throw new BadMethodCallException('factory() is not supported for the DummyApplication');
    }

    public function flush()
    {
        throw new BadMethodCallException('flush() is not supported for the DummyApplication');
    }

    public function make($abstract, array $parameters = [])
    {
        throw new BadMethodCallException('make() is not supported for the DummyApplication');
    }

    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        throw new BadMethodCallException('call() is not supported for the DummyApplication');
    }

    public function resolved($abstract)
    {
        throw new BadMethodCallException('resolved() is not supported for the DummyApplication');
    }

    public function resolving($abstract, Closure $callback = null)
    {
        throw new BadMethodCallException('resolving() is not supported for the DummyApplication');
    }

    public function afterResolving($abstract, Closure $callback = null)
    {
        throw new BadMethodCallException('afterResolving() is not supported for the DummyApplication');
    }

    public function get(string $id)
    {
        throw new BadMethodCallException('get() is not supported for the DummyApplication');
    }

    public function has(string $id)
    {
        throw new BadMethodCallException('has() is not supported for the DummyApplication');
    }
}
