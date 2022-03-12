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

    public function version(): void
    {
        throw new BadMethodCallException('version() is not supported for the DummyApplication');
    }

    public function basePath($path = ''): void
    {
        throw new BadMethodCallException('basePath() is not supported for the DummyApplication');
    }

    public function bootstrapPath($path = ''): void
    {
        throw new BadMethodCallException('bootstrapPath() is not supported for the DummyApplication');
    }

    public function configPath($path = ''): void
    {
        throw new BadMethodCallException('bootstrapPath() is not supported for the DummyApplication');
    }

    public function databasePath($path = ''): void
    {
        throw new BadMethodCallException('databasePath() is not supported for the DummyApplication');
    }

    public function resourcePath($path = ''): void
    {
        throw new BadMethodCallException('resourcePath() is not supported for the DummyApplication');
    }

    public function storagePath(): void
    {
        throw new BadMethodCallException('storagePath() is not supported for the DummyApplication');
    }

    public function environment(...$environments): void
    {
        throw new BadMethodCallException('environment() is not supported for the DummyApplication');
    }

    public function runningInConsole(): void
    {
        throw new BadMethodCallException('runningInConsole() is not supported for the DummyApplication');
    }

    public function runningUnitTests(): void
    {
        throw new BadMethodCallException('runningUnitTests() is not supported for the DummyApplication');
    }

    public function isDownForMaintenance(): void
    {
        throw new BadMethodCallException('isDownForMaintenance() is not supported for the DummyApplication');
    }

    public function registerConfiguredProviders(): void
    {
        throw new BadMethodCallException('registerConfiguredProviders() is not supported for the DummyApplication');
    }

    public function register($provider, $force = false): void
    {
        throw new BadMethodCallException('register() is not supported for the DummyApplication');
    }

    public function registerDeferredProvider($provider, $service = null): void
    {
        throw new BadMethodCallException('registerDeferredProvider() is not supported for the DummyApplication');
    }

    public function resolveProvider($provider): void
    {
        throw new BadMethodCallException('resolveProvider() is not supported for the DummyApplication');
    }

    public function boot(): void
    {
        throw new BadMethodCallException('boot() is not supported for the DummyApplication');
    }

    public function booting($callback): void
    {
        throw new BadMethodCallException('booting() is not supported for the DummyApplication');
    }

    public function booted($callback): void
    {
        throw new BadMethodCallException('booted() is not supported for the DummyApplication');
    }

    public function bootstrapWith(array $bootstrappers): void
    {
        throw new BadMethodCallException('bootstrapWith() is not supported for the DummyApplication');
    }

    public function getLocale(): void
    {
        throw new BadMethodCallException('getLocale() is not supported for the DummyApplication');
    }

    public function getProviders($provider): void
    {
        throw new BadMethodCallException('getProviders() is not supported for the DummyApplication');
    }

    public function hasBeenBootstrapped(): void
    {
        throw new BadMethodCallException('hasBeenBootstrapped() is not supported for the DummyApplication');
    }

    public function loadDeferredProviders(): void
    {
        throw new BadMethodCallException('loadDeferredProviders() is not supported for the DummyApplication');
    }

    public function setLocale($locale): void
    {
        throw new BadMethodCallException('setLocale() is not supported for the DummyApplication');
    }

    public function shouldSkipMiddleware(): void
    {
        throw new BadMethodCallException('shouldSkipMiddleware() is not supported for the DummyApplication');
    }

    public function terminate(): void
    {
        throw new BadMethodCallException('terminate() is not supported for the DummyApplication');
    }

    public function bound($abstract): void
    {
        throw new BadMethodCallException('bound() is not supported for the DummyApplication');
    }

    public function alias($abstract, $alias): void
    {
        throw new BadMethodCallException('alias() is not supported for the DummyApplication');
    }

    public function tag($abstracts, $tags): void
    {
        throw new BadMethodCallException('tag() is not supported for the DummyApplication');
    }

    public function tagged($tag): void
    {
        throw new BadMethodCallException('tagged() is not supported for the DummyApplication');
    }

    public function bind($abstract, $concrete = null, $shared = false): void
    {
        throw new BadMethodCallException('bind() is not supported for the DummyApplication');
    }

    public function bindIf($abstract, $concrete = null, $shared = false): void
    {
        throw new BadMethodCallException('bindIf() is not supported for the DummyApplication');
    }

    public function singleton($abstract, $concrete = null): void
    {
        throw new BadMethodCallException('singleton() is not supported for the DummyApplication');
    }

    public function singletonIf($abstract, $concrete = null): void
    {
        throw new BadMethodCallException('singletonIf() is not supported for the DummyApplication');
    }

    public function extend($abstract, Closure $closure): void
    {
        throw new BadMethodCallException('extend() is not supported for the DummyApplication');
    }

    public function instance($abstract, $instance): void
    {
        throw new BadMethodCallException('instance() is not supported for the DummyApplication');
    }

    public function addContextualBinding($concrete, $abstract, $implementation): void
    {
        throw new BadMethodCallException('addContextualBinding() is not supported for the DummyApplication');
    }

    public function when($concrete): void
    {
        throw new BadMethodCallException('when() is not supported for the DummyApplication');
    }

    public function factory($abstract): void
    {
        throw new BadMethodCallException('factory() is not supported for the DummyApplication');
    }

    public function flush(): void
    {
        throw new BadMethodCallException('flush() is not supported for the DummyApplication');
    }

    public function make($abstract, array $parameters = []): void
    {
        throw new BadMethodCallException('make() is not supported for the DummyApplication');
    }

    public function call($callback, array $parameters = [], $defaultMethod = null): void
    {
        throw new BadMethodCallException('call() is not supported for the DummyApplication');
    }

    public function resolved($abstract): void
    {
        throw new BadMethodCallException('resolved() is not supported for the DummyApplication');
    }

    public function resolving($abstract, Closure $callback = null): void
    {
        throw new BadMethodCallException('resolving() is not supported for the DummyApplication');
    }

    public function afterResolving($abstract, Closure $callback = null): void
    {
        throw new BadMethodCallException('afterResolving() is not supported for the DummyApplication');
    }

    public function get(string $id): void
    {
        throw new BadMethodCallException('get() is not supported for the DummyApplication');
    }

    public function has(string $id): void
    {
        throw new BadMethodCallException('has() is not supported for the DummyApplication');
    }
}
