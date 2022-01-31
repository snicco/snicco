<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

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
use Snicco\Component\ScopableWP\ScopableWP;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;

/**
 * @api
 */
final class BladeStandalone
{

    private string $view_cache_directory;
    private array $view_directories;
    private ViewComposerCollection $composers;

    /**
     * @var Container|Application
     */
    private $illuminate_container;

    public function __construct(
        string $view_cache_directory,
        array $view_directories,
        ViewComposerCollection $composers
    ) {
        $this->view_cache_directory = $view_cache_directory;
        $this->view_directories = $view_directories;
        $this->composers = $composers;
        $this->illuminate_container = Container::getInstance();
        if (!Facade::getFacadeApplication() instanceof IlluminateContainer) {
            Facade::setFacadeApplication($this->illuminate_container);
        }
    }

    public function getBladeViewFactory(): BladeViewFactory
    {
        return $this->illuminate_container[BladeViewFactory::class];
    }

    public function boostrap()
    {
        $this->bindDependencies();
        $this->bootIlluminateViewServiceProvider();
        $this->listenToEvents();
        $this->disableUnsupportedDirectives();
        $this->bindFrameworkDependencies();
    }

    private function bindDependencies()
    {
        if ($this->illuminate_container->has('config')) {
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
        $this->illuminate_container->bindIf(Factory::class, function () {
            return $this->illuminate_container->make('view');
        });
        $this->illuminate_container->bindIf(Application::class, function () {
            return new DummyApplication();
        });
    }

    // Register custom blade directives

    private function bootIlluminateViewServiceProvider(): void
    {
        ((new ViewServiceProvider($this->illuminate_container)))->register();
    }

    // These are all the dependencies that Blade expects to be present in the global service container.

    private function listenToEvents()
    {
        /** @var Dispatcher $laravel_dispatcher */
        $event_dispatcher = $this->illuminate_container->make('events');
        $event_dispatcher->listen('composing:*', function ($event_name, $payload) {
            $this->composers->compose(new BladeView($payload[0]));
        });
    }

    // Make sure that our passed view composer collection is run when blade creates views.

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

    // Bind the dependencies that are needed for our view component to work.

    private function bindFrameworkDependencies()
    {
        $this->illuminate_container->resolving(BladeComponent::class,
            function (BladeComponent $component) {
                $component->setEngine($this->illuminate_container->make(BladeViewFactory::class));
            }
        );

        $this->illuminate_container->bindIf(BladeViewFactory::class, function ($container) {
            return new BladeViewFactory($container->make('view'), $this->view_directories);
        }, true);
    }

    /**
     * @api
     */
    public function bindWordPressDirectives(ScopableWP $wp = null): void
    {
        $wp = $wp ?: new ScopableWP();

        Blade::if('auth', fn() => $wp->isUserLoggedIn());

        Blade::if('guest', fn() => !$wp->isUserLoggedIn());

        Blade::if('role', function ($expression) use ($wp) {
            if ($expression === 'admin') {
                $expression = 'administrator';
            }
            $user = $wp->getCurrentUser();
            if (!empty($user->roles) && is_array($user->roles)
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

}