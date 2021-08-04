<?php


    declare(strict_types = 1);


    namespace Snicco\Blade;

    use Illuminate\Container\Container as IlluminateContainer;
    use Illuminate\Contracts\Container\Container;
    use Illuminate\Contracts\Foundation\Application;
    use Illuminate\Contracts\View\Factory;
    use Illuminate\Events\Dispatcher;
    use Illuminate\Filesystem\Filesystem;
    use Illuminate\Support\Facades\Facade;
    use Illuminate\View\ViewServiceProvider;
    use Snicco\Contracts\ServiceProvider;
    use Snicco\Contracts\ViewEngineInterface;
    use SniccoAdapter\BaseContainerAdapter;

    class BladeServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $container = $this->parseContainer();

            $cache_dir = $this->config->get('view.blade_cache', $this->app->storagePath("framework".DIRECTORY_SEPARATOR.'views'));

            $this->config->set('view.compiled', $cache_dir);

            $this->setUpBindings($container);
            $this->registerLaravelProvider($container);

            $this->container->singleton(ViewEngineInterface::class, function () {

                return new BladeEngine(
                    $this->container->make('view'),
                );

            });

            $this->createBindingForBladeComponents($container);

        }

        function bootstrap() : void
        {
            //
        }

        private function registerLaravelProvider (Container $container) {


            ( ( new ViewServiceProvider($container)) )->register();

        }

        private function parseContainer () : Container {

           return $this->container instanceof BaseContainerAdapter
                ? $this->container->implementation()
                : IlluminateContainer::getInstance();

        }

        private function setUpBindings(Container $container)
        {

            $container->bindIf('files', function () {
                return new Filesystem();
            }, true);

            $container->bindIf('events', function () {
                return new Dispatcher();
            }, true);

            $container->instance('config', $this->config);

            $container = $this->parseContainer();
            Facade::setFacadeApplication($container);
            IlluminateContainer::setInstance($container);

        }

        private function createBindingForBladeComponents(Container $container)
        {

            $container->bindIf(Factory::class,function (Container $c) {

                return $c->make('view');

            });

            $container->bindIf(Application::class,function () use ($container) {

                return new DummyApplication();

            });

            $container->resolving(BladeComponent::class, function (BladeComponent $component,Container $container) {

                $component->setEngine($container->make(ViewEngineInterface::class));

            });


        }

    }