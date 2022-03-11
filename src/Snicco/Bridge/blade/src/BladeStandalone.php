<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use ArrayAccess;
use BadMethodCallException;
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
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;

use function in_array;
use function is_array;

final class BladeStandalone
{
    private string $view_cache_directory;

    /**
     * @var string[]
     */
    private array $view_directories;

    /**
     * @var Application|Container
     */
    private $illuminate_container;

    private BladeViewComposer $blade_view_composer;

    /**
     * @param string[] $view_directories
     */
    public function __construct(
        string $view_cache_directory,
        array $view_directories,
        ViewComposerCollection $composers
    ) {
        $this->view_cache_directory = $view_cache_directory;
        $this->view_directories = $view_directories;
        $this->blade_view_composer = new BladeViewComposer($composers);
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

    /**
     * @api
     */
    public function bindWordPressDirectives(BetterWPAPI $wp = null): void
    {
        $wp = $wp ?: new BetterWPAPI();

        Blade::if('auth', fn () => $wp->isUserLoggedIn());

        Blade::if('guest', fn () => ! $wp->isUserLoggedIn());

        Blade::if('role', function (string $expression) use ($wp) {
            if ('admin' === $expression) {
                $expression = 'administrator';
            }
            $user = $wp->currentUser();
            if (! empty($user->roles) && is_array($user->roles)
                && in_array(
                    $expression,
                    $user->roles,
                    true
                )) {
                return true;
            }

            return false;
        });
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
            $this->illuminate_container->singleton('config', function () {
                $config = new Fluent();
                $config['view.compiled'] = $this->view_cache_directory;
                $config['view.paths'] = $this->view_directories;

                return $config;
            });
        }

        $this->illuminate_container->bindIf('files', function () {
            return new Filesystem();
        }, true);

        $this->illuminate_container->bindIf(
            'events',
            function () {
                return new Dispatcher();
            },
            true
        );
        /**
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress MixedInferredReturnType
         */
        $this->illuminate_container->bindIf(Factory::class, function (): Factory {
            return $this->illuminate_container->make('view');
        });
        $this->illuminate_container->bindIf(Application::class, function (): DummyApplication {
            return new DummyApplication();
        });
    }

    private function bootIlluminateViewServiceProvider(): void
    {
        /**
         * @psalm-suppress PossiblyInvalidArgument
         */
        ((new ViewServiceProvider($this->illuminate_container)))->register();
    }

    private function listenToEvents(): void
    {
        /** @var Dispatcher $event_dispatcher */
        $event_dispatcher = $this->illuminate_container->make('events');
        $event_dispatcher->listen('composing:*', [$this->blade_view_composer, 'handleEvent']);
    }

    private function disableUnsupportedDirectives(): void
    {
        Blade::directive('service', function () {
            throw new BadMethodCallException(
                'The service directive is not supported. Dont use it. Its evil.'
            );
        });

        Blade::directive('csrf', function () {
            throw new BadMethodCallException(
                'The csrf directive is not supported as it requires the entire laravel framework.'
            );
        });

        Blade::directive('method', function () {
            throw new BadMethodCallException(
                'The method directive is not supported because form-method spoofing is not supported in WordPress.'
            );
        });
    }

    /**
     * @psalm-suppress MixedArgument
     */
    private function bindFrameworkDependencies(): void
    {
        $this->illuminate_container->bindIf(BladeViewFactory::class, function (IlluminateContainer $container) {
            return new BladeViewFactory($container->make('view'), $this->view_directories);
        }, true);
    }
}
