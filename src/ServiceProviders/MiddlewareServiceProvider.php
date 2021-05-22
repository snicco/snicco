<?php


    declare(strict_types = 1);


    namespace WPEmerge\ServiceProviders;

    use Slim\Csrf\Guard;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Http\Request;
    use WPEmerge\Middleware\Authenticate;
    use WPEmerge\Middleware\Authorize;
    use WPEmerge\Middleware\EvaluateResponseMiddleware;
    use WPEmerge\Middleware\RedirectIfAuthenticated;
    use WPEmerge\Middleware\RouteRunner;

    class MiddlewareServiceProvider extends ServiceProvider
    {

        const GLOBAL_MIDDLEWARE_ALREADY_HANDLED = 'global.middleware.run';

        public function register() : void
        {

            $this->bindConfig();

            $this->bindEvaluateResponseMiddleware();

            $this->bindRouteRunnerMiddleware();


        }

        function bootstrap() : void
        {
            //
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

            $this->container->instance(self::GLOBAL_MIDDLEWARE_ALREADY_HANDLED, false);

        }

        private function bindEvaluateResponseMiddleware()
        {

            $this->container->singleton(EvaluateResponseMiddleware::class, function () {

                /** @var Request $request */
                $request = $this->container->make(Request::class);

                $is_web = $request->getType() === IncomingWebRequest::class;

                $must_match = $is_web && $this->config->get('routing.must_match_web_routes', false );

                return new EvaluateResponseMiddleware($must_match);

            });

        }

        private function bindRouteRunnerMiddleware()
        {

            $this->container->singleton(RouteRunner::class, function () {

                $runner = new RouteRunner(
                    $this->container->make(ResponseFactory::class),
                    $this->container
                );

                $runner->withMiddlewareGroup('web', $this->config->get('middleware.groups.web', []));
                $runner->withMiddlewareGroup('admin', $this->config->get('middleware.groups.admin', []));
                $runner->withMiddlewareGroup('ajax', $this->config->get('middleware.groups.ajax', []));


                if ( ! $this->globalMiddlewareHandledByKernel() ) {

                    $runner->withMiddlewareGroup('global', $this->config->get('middleware.groups.global', []));

                }

                $runner->middlewarePriority($this->config->get('middleware.priority', []));

                $runner->middlewareAliases($this->config->get('middleware.aliases', []));

                return $runner;

            });

        }

        private function globalMiddlewareHandledByKernel () :bool {

            return $this->container->make(self::GLOBAL_MIDDLEWARE_ALREADY_HANDLED);

        }

    }