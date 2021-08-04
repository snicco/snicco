<?php


    declare(strict_types = 1);


    namespace Snicco\Blade;

    use Illuminate\Contracts\Container\Container as IlluminateContainerInterface;
    use Illuminate\Contracts\Foundation\Application;
    use Illuminate\Contracts\View\Factory;
    use Illuminate\Events\Dispatcher;
    use Illuminate\Filesystem\Filesystem;
    use Illuminate\View\ViewServiceProvider;
    use Snicco\Contracts\ServiceProvider;
    use Snicco\Contracts\ViewEngineInterface;
    use Snicco\Traits\ReliesOnIlluminateContainer;

    class BladeServiceProvider extends ServiceProvider
    {

        use ReliesOnIlluminateContainer;

        public function register() : void
        {

            $container = $this->parseIlluminateContainer();

            $cache_dir = $this->config->get('view.blade_cache', $this->app->storagePath("framework".DIRECTORY_SEPARATOR.'views'));

            $this->config->set('view.compiled', $cache_dir);

            $this->setIlluminateBindings($container);
            $this->registerLaravelProvider($container);

            $this->registerBladeViewEngine();

            $this->setBladeComponentBindings($container);

        }

        function bootstrap() : void
        {
            //
        }

        private function registerLaravelProvider(IlluminateContainerInterface $container)
        {

            ((new ViewServiceProvider($container)))->register();

        }

        private function setIlluminateBindings(IlluminateContainerInterface $container)
        {

            $container->bindIf('files', function () {

                return new Filesystem();

            }, true);

            $container->bindIf('events', function () {

                return new Dispatcher();

            }, true);

            $container->instance('config', $this->config);

            $this->setFacadeContainer($container);
            $this->setGlobalContainerInstance($container);

        }

        private function setBladeComponentBindings(IlluminateContainerInterface $container)
        {

            $container->bindIf(Factory::class, function (IlluminateContainerInterface $c) {

                return $c->make('view');

            });

            $container->bindIf(Application::class, function () use ($container) {

                return new DummyApplication();

            });

            $container->resolving(BladeComponent::class, function (BladeComponent $component, IlluminateContainerInterface $container) {

                $component->setEngine($container->make(ViewEngineInterface::class));

            });

        }

        private function registerBladeViewEngine() : void
        {

            $this->container->singleton(ViewEngineInterface::class, function () {

                return new BladeEngine(
                    $this->container->make('view'),
                );

            });
        }

    }