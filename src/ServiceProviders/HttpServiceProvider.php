<?php


    declare(strict_types = 1);


    namespace WPEmerge\ServiceProviders;

    use Nyholm\Psr7\Factory\Psr17Factory as NyholmFactoryImplementation;
    use Psr\Http\Message\ResponseFactoryInterface as Prs17ResponseFactory;
    use Psr\Http\Message\StreamFactoryInterface;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Contracts\ViewServiceInterface;
    use WPEmerge\Http\HttpResponseFactory;
    use WPEmerge\Middleware\CsrfProtection;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Middleware\Authorize;
    use WPEmerge\Middleware\Authenticate;
    use WPEmerge\Middleware\RedirectIfAuthenticated;
    use WPEmerge\Routing\Router;

    class HttpServiceProvider extends ServiceProvider
    {


        public function register() : void
        {


            $this->config->extend('middleware.aliases', [

                'csrf' => CsrfProtection::class,
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

            $this->container->singleton(HttpKernel::class, function () {

                return new HttpKernel(

                    $this->container[Router::class],
                    $this->container,
                    $this->container[ErrorHandlerInterface::class]

                );


            });

            $this->container->singleton(Prs17ResponseFactory::class, function () {

                return new NyholmFactoryImplementation();

            });

            $this->container->singleton(StreamFactoryInterface::class, function () {

                return new NyholmFactoryImplementation();

            });

            $this->container->singleton(ResponseFactory::class, function () {

                return new HttpResponseFactory(
                    $this->container->make(ViewServiceInterface::class),
                    $this->container->make(Prs17ResponseFactory::class),
                    $this->container->make(StreamFactoryInterface::class),

                );

            });

        }

        public function bootstrap() : void
        {

            $kernel = $this->container->make(HttpKernel::class);

            $kernel->setRouteMiddlewareAliases($this->config->get('middleware.aliases', []));
            $kernel->setMiddlewareGroups($this->config->get('middleware.groups', []));
            $kernel->setMiddlewarePriority($this->config->get('middleware.priority', []));


        }

    }
