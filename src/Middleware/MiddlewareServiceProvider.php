<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware;

    use Snicco\Contracts\ErrorHandlerInterface;
    use Snicco\Contracts\ServiceProvider;
    use Snicco\Http\ResponseEmitter;
    use Snicco\Http\ResponseFactory;
    use Snicco\Middleware\Core\EvaluateResponseMiddleware;
    use Snicco\Middleware\Core\OpenRedirectProtection;
    use Snicco\Middleware\Core\OutputBufferMiddleware;
    use Snicco\Middleware\Core\RouteRunner;
    use Snicco\Routing\Pipeline;

    class MiddlewareServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();

            $this->bindMiddlewareStack();

            $this->bindEvaluateResponseMiddleware();

            $this->bindRouteRunnerMiddleware();

            $this->bindMiddlewarePipeline();

            $this->bindOutputBufferMiddleware();

            $this->bindTrailingSlash();

            $this->bindWww();

            $this->bindOpenRedirectProtection();

        }

        function bootstrap() : void
        {
            //
        }

        private function bindConfig()
        {

            $this->config->extend('middleware.aliases', [

                'auth' => Authenticate::class,
                'guest' => RedirectIfAuthenticated::class,
                'can' => Authorize::class,
                'json' => JsonPayload::class,
                'robots' => NoRobots::class,
                'secure' => Secure::class,
                'signed' => ValidateSignature::class,

            ]);

            $this->config->extend('middleware.groups', [

                'global' => [],
                'web' => [],
                'ajax' => [],
                'admin' => [],

            ]);

            $this->config->extend('middleware.priority', [ Secure::class, Www::class, TrailingSlash::class,]);
            $this->config->extend('middleware.always_run_global', false);


        }

        private function bindEvaluateResponseMiddleware()
        {

            $this->container->singleton(EvaluateResponseMiddleware::class, function () {

                $is_web = $this->requestEndpoint() === 'frontend';

                $must_match = $is_web && $this->config->get('routing.must_match_web_routes', false );

                return new EvaluateResponseMiddleware($must_match);

            });

        }

        private function bindRouteRunnerMiddleware()
        {

            $this->container->singleton(RouteRunner::class, function () {

                return new RouteRunner(
                    $this->container->make(ResponseFactory::class),
                    $this->container,
                    $this->container->make(Pipeline::class),
                    $this->container->make(MiddlewareStack::class)
                );

            });

        }

        private function bindMiddlewarePipeline()
        {
            $this->container->bind(Pipeline::class, function () {

                return new Pipeline(
                    $this->container,
                    $this->container->make(ErrorHandlerInterface::class)
                );

            });
        }

        private function bindMiddlewareStack()
        {

            $this->container->singleton(MiddlewareStack::class, function () {

                $stack = new MiddlewareStack();

                if ( $this->config->get('middleware.disabled', false ) ) {
                    $stack->disableAllMiddleware();
                    return $stack;
                }

                foreach ($this->config->get('middleware.groups') as $name => $middleware) {

                    $stack->withMiddlewareGroup($name, $middleware);

                }

                $stack->middlewarePriority($this->config->get('middleware.priority', []));
                $stack->middlewareAliases($this->config->get('middleware.aliases', []));

                return $stack;

            });
        }

        private function bindOutputBufferMiddleware()
        {

            $this->container->singleton(OutputBufferMiddleware::class, function () {

                $middleware = new OutputBufferMiddleware(
                    $this->container->make(ResponseEmitter::class),
                    $this->container->make(ResponseFactory::class),
                );

                $this->container->instance(OutputBufferMiddleware::class, $middleware);

                return $middleware;

            });

        }

        private function bindTrailingSlash()
        {

            $this->container->singleton(TrailingSlash::class, function () {

                    return new TrailingSlash(
                        $this->withSlashes()
                    );

            });

        }

        private function bindWww()
        {

             $this->container->singleton(Www::class, function () {

                 return new Www(
                     $this->siteUrl()
                 );

            });

        }

        private function bindOpenRedirectProtection()
        {
            $this->container->singleton(OpenRedirectProtection::class, function () {

                return new OpenRedirectProtection(
                    $this->siteUrl()
                );

            });
        }


    }