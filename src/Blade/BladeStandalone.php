<?php

declare(strict_types=1);

namespace Snicco\Blade;

use RuntimeException;
use Snicco\Support\WP;
use Illuminate\Support\Fluent;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Facade;
use Snicco\View\ViewComposerCollection;
use Illuminate\View\ViewServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Container\Container as IlluminateContainer;

/**
 * @api
 */
final class BladeStandalone
{
    
    /**
     * @var string
     */
    private $view_cache_directory;
    
    /**
     * @var array
     */
    private $view_directories;
    
    /**
     * @var ViewComposerCollection
     */
    private $composers;
    
    /**
     * @var Container|Application
     */
    private $illuminate_container;
    
    public function __construct(string $view_cache_directory, array $view_directories, ViewComposerCollection $composers)
    {
        $this->view_cache_directory = $view_cache_directory;
        $this->view_directories = $view_directories;
        $this->composers = $composers;
        $this->illuminate_container = Container::getInstance();
        if ( ! Facade::getFacadeApplication() instanceof IlluminateContainer) {
            Facade::setFacadeApplication($this->illuminate_container);
        }
    }
    
    /**
     * After bootstrapping Blade this view factory should be used in the framework view component.
     *
     * @return BladeViewFactory
     */
    public function getBladeViewFactory() :BladeViewFactory
    {
        return $this->illuminate_container[BladeViewFactory::class];
    }
    
    public function boostrap()
    {
        $this->bindDependencies();
        
        $this->bootIlluminateViewServiceProvider();
        
        $this->listenToEvents();
        
        $this->bindWordPressDirectives();
        
        $this->bindFrameworkDependencies();
    }
    
    // The ViewServiceProvider will take care of registering all the internal bindings that blade needs to function.
    private function bootIlluminateViewServiceProvider() :void
    {
        ((new ViewServiceProvider($this->illuminate_container)))->register();
    }
    
    // Register custom blade directives
    private function bindWordPressDirectives() :void
    {
        Blade::if('auth', function () { return WP::isUserLoggedIn(); });
        
        Blade::if('guest', function () { return ! WP::isUserLoggedIn(); });
        
        Blade::if('role', function ($expression) {
            if ($expression === 'admin') {
                $expression = 'administrator';
            }
            
            return WP::userIs($expression);
        });
        
        Blade::directive('service', function () {
            throw new RuntimeException(
                'The service directive is not allowed. Dont use it. Its evil.'
            );
        });
    }
    
    // These are all the dependencies that Blade expects to be present in the global service container.
    private function bindDependencies()
    {
        if ($this->illuminate_container->has('config')) {
            $config = $this->illuminate_container->get('config');
            $config['view.compiled'] = $this->view_cache_directory;
            $config['view.paths'] = $this->view_directories;
        }
        else {
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
    
    // Make sure that our passed view composer collection is run when blade creates views.
    private function listenToEvents()
    {
        /** @var Dispatcher $laravel_dispatcher */
        $event_dispatcher = $this->illuminate_container->make('events');
        $event_dispatcher->listen('composing:*', function ($event_name, $payload) {
            $this->composers->compose(new BladeView($payload[0]));
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
            return new BladeViewFactory($container->make('view'));
        }, true);
    }
    
}