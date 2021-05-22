<?php


    declare(strict_types = 1);


    namespace WPEmerge\ServiceProviders;

    use Nyholm\Psr7\Factory\Psr17Factory as NyholmFactoryImplementation;
    use Psr\Http\Message\ResponseFactoryInterface as Prs17ResponseFactory;
    use Psr\Http\Message\StreamFactoryInterface;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Contracts\ViewServiceInterface;
    use WPEmerge\Http\HttpResponseFactory;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Routing\Pipeline;

    class HttpServiceProvider extends ServiceProvider
    {

        public function register() : void
        {


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

                $kernel->alwaysWithGlobalMiddleware($this->config->get('middleware.groups.global', [] ) );

            }



        }

        private function bindKernel()
        {

            $this->container->singleton(HttpKernel::class, function () {

                return new HttpKernel(

                    $this->container->make(Pipeline::class),
                    new ResponseEmitter()

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
