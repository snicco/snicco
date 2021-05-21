<?php


    declare(strict_types = 1);


    namespace WPEmerge\ServiceProviders;

    use Nyholm\Psr7\Factory\Psr17Factory as NyholmFactoryImplementation;
    use Psr\Http\Message\ResponseFactoryInterface as Prs17ResponseFactory;
    use Psr\Http\Message\StreamFactoryInterface;
    use Slim\Csrf\Guard;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Contracts\ViewServiceInterface;
    use WPEmerge\Http\HttpResponseFactory;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Middleware\Authorize;
    use WPEmerge\Middleware\Authenticate;
    use WPEmerge\Middleware\RedirectIfAuthenticated;
    use WPEmerge\Routing\Router;

    class HttpServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();

            $this->bindKernel();

            $this->bindConcretePsr17ResponseFactory();

            $this->bindPsr17ResponseFactoryInterface();

            $this->bindPsr17StreamFactory();


        }

        public function bootstrap() : void
        {

            /** @var HttpKernel $kernel */
            $kernel = $this->container->make(HttpKernel::class);

            if ($this->config->get('always_run_middleware', false)) {

                $kernel->alwaysWithGlobalMiddleware();

            }


        }

        private function bindConfig()
        {

            $this->config->extend('middleware.aliases', [

                'csrf' => Guard::class,
                'auth' => Authenticate::class,
                'guest' => RedirectIfAuthenticated::class,
                'can' => Authorize::class,

            ]);

            $this->config->extend('middleware.groups', [

                'global' => [],
                'web' => [],
                'ajax' => [],
                'admin' => [],

            ]);

            $this->config->extend('middleware.priority', []);

            $this->config->extend('always_run_middleware', false);

        }

        private function bindKernel()
        {
            $this->container->singleton(HttpKernel::class, function () {

                return new HttpKernel(

                    $this->container,
                    $this->container->make(AbstractRouteCollection::class)

                );

            });
        }

        private function bindConcretePsr17ResponseFactory() : void
        {

            $this->container->singleton('psr17.response.factory', function () {

                return new NyholmFactoryImplementation();

            });
        }

        private function bindPsr17StreamFactory() : void
        {

            $this->container->singleton(StreamFactoryInterface::class, function () {

                return new NyholmFactoryImplementation();

            });
        }

        private function bindPsr17ResponseFactoryInterface() : void
        {

            $this->container->singleton(ResponseFactory::class, function () {

                return new HttpResponseFactory(
                    $this->container->make(ViewServiceInterface::class),
                    $this->container->make('psr17.response.factory'),
                    $this->container->make(StreamFactoryInterface::class),

                );

            });

            $this->container->singleton(Prs17ResponseFactory::class, function () {

                return $this->container->make(ResponseFactory::class);

            });

        }




    }
