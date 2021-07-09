<?php


    declare(strict_types = 1);


    namespace BetterWP\Blade;

    use Illuminate\Container\Container as IlluminateContainer;
    use Illuminate\Contracts\Container\Container;
    use Illuminate\Contracts\Foundation\Application;
    use Illuminate\Events\Dispatcher;
    use Illuminate\Filesystem\Filesystem;
    use Illuminate\Support\Facades\Facade;
    use Illuminate\Contracts\View\Factory;
    use Illuminate\View\View;
    use Illuminate\View\ViewServiceProvider;
    use SniccoAdapter\BaseContainerAdapter;
    use BetterWP\Contracts\ServiceProvider;
    use BetterWP\Contracts\ViewEngineInterface;
    use BetterWP\Contracts\ViewFactoryInterface;
    use BetterWP\Support\Arr;

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

            $container = $this->parseContainer();

            /** @var Dispatcher $dispatcher */
            $dispatcher = $container->make('events');

            $dispatcher->listen('composing: *', function ( $event_name, $payload ) use ($container) {

                /** @var View $view */
                $view = Arr::firstEl($payload);

                /** @var ViewFactoryInterface $view_service */
                $view_service = $container->make(ViewFactoryInterface::class);

                $view_service->compose(new BladeView($view));

            });



        }

        private function registerLaravelProvider (Container $container) {


            ( ( new ViewServiceProvider($container)) )->register();

        }

        private function parseContainer () : Container {

           return  $this->container instanceof BaseContainerAdapter
                ? $this->container->implementation()
                : new IlluminateContainer();

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


            if( ! Facade::getFacadeApplication() instanceof Container) {

                Facade::setFacadeApplication($container);

            }

            if( ! IlluminateContainer::getInstance() instanceof Container) {

                IlluminateContainer::setInstance($container);

            }

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