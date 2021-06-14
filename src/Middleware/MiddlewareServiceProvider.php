<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\Core\EvaluateResponseMiddleware;
    use WPEmerge\Middleware\Core\RouteRunner;
    use WPEmerge\Routing\Pipeline;

    class MiddlewareServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();

            $this->bindMiddlewareStack();

            $this->bindEvaluateResponseMiddleware();

            $this->bindRouteRunnerMiddleware();

            $this->bindMiddlewarePipeline();

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

            ]);

            $this->config->extend('middleware.groups', [

                'global' => [
                    Secure::class
                ],
                'web' => [],
                'ajax' => [],
                'admin' => [],

            ]);

            $this->config->extend('middleware.priority', [Secure::class]);
            $this->config->extend('middleware.priority', []);

            $this->config->extend('middleware.always_run_global', false);

            $this->config->extend('middleware.unique', []);


        }

        private function bindEvaluateResponseMiddleware()
        {

            $this->container->singleton(EvaluateResponseMiddleware::class, function () {

                $is_web = $this->requestType() === IncomingWebRequest::class;

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

                foreach ($this->config->get('middleware.groups') as $name => $middleware) {

                    $stack->withMiddlewareGroup($name, $middleware);

                }

                $stack->middlewarePriority($this->config->get('middleware.priority', []));
                $stack->middlewareAliases($this->config->get('middleware.aliases', []));

                return $stack;

            });
        }



    }