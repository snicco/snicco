<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Nyholm\Psr7\Factory\Psr17Factory as NyholmFactoryImplementation;
    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\ResponseFactoryInterface as Prs17ResponseFactory;
    use Psr\Http\Message\StreamFactoryInterface;
    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Contracts\ViewFactoryInterface;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Session;

    class HttpServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindKernel();

            $this->bindConcretePsr17ResponseFactory();

            $this->bindPsr17ResponseFactoryInterface();

            $this->bindPsr17StreamFactory();

            $this->bindCookies();

            $this->bindRedirector();

        }

        public function bootstrap() : void
        {

        }

        private function bindKernel()
        {

            $this->container->singleton(HttpKernel::class, function () {

                $kernel = new HttpKernel(

                    $this->container->make(Pipeline::class),

                );

                if ($this->config->get('middleware.always_run_global', false)) {

                    $kernel->alwaysWithGlobalMiddleware($this->config->get('middleware.groups.global', [] ) );

                }

                $kernel->addUniqueMiddlewares($this->config->get('middleware.unique', []));

                return $kernel;

            });
        }

        private function bindConcretePsr17ResponseFactory() : void
        {

            $this->container->singleton(Prs17ResponseFactory::class, function () {

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

                return new ResponseFactory(
                    $this->container->make(ViewFactoryInterface::class),
                    $this->container->make(Prs17ResponseFactory::class),
                    $this->container->make(StreamFactoryInterface::class),
                    $this->container->make(AbstractRedirector::class),
                );

            });


        }

        private function bindCookies()
        {

            $this->container->singleton(Cookies::class, function () {

                $cookies = new Cookies();
                $cookies->setDefaults([
                    'value' => '',
                    'domain' => null,
                    'hostonly' => true,
                    'path' => null,
                    'expires' => null,
                    'secure' => true,
                    'httponly' => false,
                    'samesite' => 'lax'
                ]);

                return $cookies;

            });
        }

        private function bindRedirector()
        {
            $this->container->singleton(AbstractRedirector::class, function () {

                $redirector = $this->container->make(Redirector::class);

                if ( $this->sessionEnabled()) {

                    return new StatefulRedirector(
                        $this->container->make(Session::class),
                        $this->container->make(UrlGenerator::class),
                        $this->container->make(ResponseFactoryInterface::class)
                    );
                }

                return $redirector;

            });
        }


    }
