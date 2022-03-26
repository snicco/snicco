<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use ArrayAccess;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Fluent;
use Illuminate\View\ViewServiceProvider;
use Snicco\Bridge\Blade\Exception\UnsupportedDirective;
use Snicco\Component\Templating\Context\ViewContextResolver;

final class BladeStandalone
{
    /**
     * @var array these directives require the full laravel framework and can not be used
     */
    private const UNSUPPORTED_DIRECTIVES = [
        'auth',
        'guest',
        'method',
        'csrf',
        'service',
        'env',
        'production',
        'can',
        'cannot',
        'canany',
        'dd',
        'dump',
        'lang',
        'choice',
        'error',
        'inject',
    ];

    private string $view_cache_directory;

    /**
     * @var string[]
     */
    private array $view_directories;

    private Container $illuminate_container;

    private BladeViewComposer $blade_view_composer;

    /**
     * @param string[] $view_directories
     */
    public function __construct(
        string $view_cache_directory,
        array $view_directories,
        ViewContextResolver $context_resolver
    ) {
        $this->view_cache_directory = $view_cache_directory;
        $this->view_directories = $view_directories;
        $this->blade_view_composer = new BladeViewComposer($context_resolver);
        $this->illuminate_container = Container::getInstance();
        if (! Facade::getFacadeApplication() instanceof IlluminateContainer) {
            /** @psalm-suppress InvalidArgument */
            Facade::setFacadeApplication($this->illuminate_container);
        }
    }

    /**
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function getBladeViewFactory(): BladeViewFactory
    {
        return $this->illuminate_container->get(BladeViewFactory::class);
    }

    public function boostrap(): void
    {
        $this->bindDependencies();
        $this->bootIlluminateViewServiceProvider();
        $this->listenToEvents();
        $this->disableUnsupportedDirectives();
        $this->bindFrameworkDependencies();
    }

    private function bindDependencies(): void
    {
        if ($this->illuminate_container->has('config')) {
            /** @var ArrayAccess $config */
            $config = $this->illuminate_container->get('config');
            $config['view.compiled'] = $this->view_cache_directory;
            $config['view.paths'] = $this->view_directories;
        } else {
            // Blade only needs some config element that works with array access.
            $this->illuminate_container->singleton('config', function (): Fluent {
                $config = new Fluent();
                $config['view.compiled'] = $this->view_cache_directory;
                $config['view.paths'] = $this->view_directories;

                return $config;
            });
        }

        $this->illuminate_container->bindIf('files', fn (): Filesystem => new Filesystem(), true);

        $this->illuminate_container->bindIf('events', fn (): Dispatcher => new Dispatcher(), true);
        /**
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress MixedInferredReturnType
         */
        $this->illuminate_container->bindIf(
            Factory::class,
            fn (): Factory => $this->illuminate_container->make('view')
        );
        $this->illuminate_container->bindIf(Application::class, fn (): DummyApplication => new DummyApplication());
    }

    private function bootIlluminateViewServiceProvider(): void
    {
        /**
         * @psalm-suppress InvalidArgument
         */
        ((new ViewServiceProvider($this->illuminate_container)))
            ->register();
    }

    private function listenToEvents(): void
    {
        /** @var Dispatcher $event_dispatcher */
        $event_dispatcher = $this->illuminate_container->make('events');
        $event_dispatcher->listen('composing:*', [$this->blade_view_composer, 'handleEvent']);
    }

    private function disableUnsupportedDirectives(): void
    {
        foreach (self::UNSUPPORTED_DIRECTIVES as $directive) {
            Blade::directive($directive, function () use ($directive): void {
                throw new UnsupportedDirective($directive);
            });
        }
    }

    /**
     * @psalm-suppress MixedArgument
     */
    private function bindFrameworkDependencies(): void
    {
        $this->illuminate_container->bindIf(
            BladeViewFactory::class,
            fn (IlluminateContainer $container): BladeViewFactory => new BladeViewFactory($container->make(
                'view'
            ), $this->view_directories),
            true
        );
    }
}
