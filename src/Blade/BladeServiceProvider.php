<?php


    declare(strict_types = 1);


    namespace WPEmerge\Blade;

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
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Contracts\ViewEngineInterface;
    use WPEmerge\Contracts\ViewServiceInterface;
    use WPEmerge\Support\Arr;

    class BladeServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $container = $this->parseContainer();

            $views = $this->config->get('blade.views', []);
            $cache_dir = $this->config->get('blade.cache', null);

            $this->config->set('view.paths', Arr::wrap($views));
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

                /** @var ViewServiceInterface $view_service */
                $view_service = $container->make(ViewServiceInterface::class);

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