<?php


    declare(strict_types = 1);


    namespace Tests\traits;

    use Contracts\ContainerAdapter;
    use Tests\stubs\Middleware\BarMiddleware;
    use Tests\stubs\Middleware\BazMiddleware;
    use Tests\stubs\Middleware\FooBarMiddleware;
    use Tests\stubs\Middleware\FooMiddleware;
    use Tests\stubs\TestRequest;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\ExceptionHandling\NullErrorHandler;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Middleware\Core\RouteRunner;
    use WPEmerge\Middleware\MiddlewareStack;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Routing\RoutingServiceProvider;

    trait TestHelpers
    {

        /** @var MiddlewareStack */
        protected $middleware_stack;

        protected function newRouteCollection() : RouteCollection
        {

            $condition_factory = new ConditionFactory($this->conditions(), $this->container);
            $handler_factory = new RouteActionFactory([], $this->container);

            return new RouteCollection(
                $this->createRouteMatcher(),
                $condition_factory,
                $handler_factory

            );


        }

        protected function createRoutes(\Closure $routes)
        {

            $this->routes = $this->newRouteCollection();

            $this->router = $this->newRouter();

            $routes();

            $this->router->loadRoutes();

        }

        protected function newRouter() : Router
        {

            return new Router($this->container, $this->routes);

        }

        protected function newUrlGenerator() : UrlGenerator
        {
            return new UrlGenerator(new FastRouteUrlGenerator($this->routes));
        }

        protected function newKernel(array $with_middleware = []) :HttpKernel
        {

            $pipeline = new Pipeline($this->container);

            $this->container->instance(ErrorHandlerInterface::class, new NullErrorHandler());
            $this->container->instance(AbstractRouteCollection::class, $this->routes);
            $this->container->instance(ResponseFactory::class, $factory = $this->createResponseFactory());
            $this->container->instance(ContainerAdapter::class, $this->container);

            $middleware_stack = new MiddlewareStack();
            $middleware_stack->middlewareAliases([
                'foo' => FooMiddleware::class,
                'bar' => BarMiddleware::class,
                'baz' => BazMiddleware::class,
                'foobar' => FooBarMiddleware::class,
            ]);
            foreach ($with_middleware as $group_name => $middlewares) {

                $middleware_stack->withMiddlewareGroup($group_name, $middlewares);

            }

            $router_runner = new RouteRunner($factory, new Pipeline($this->container), $middleware_stack);

            $this->container->instance(RouteRunner::class, $router_runner);
            $this->container->instance(MiddlewareStack::class, $middleware_stack);
            $this->middleware_stack = $middleware_stack;


            return new HttpKernel($pipeline);


        }

        protected function runKernel (IncomingRequest $request, HttpKernel $kernel = null) {

            $kernel = $kernel ?? $this->newKernel();
            $kernel->run($request);

        }

        protected function runKernelAndGetOutput(IncomingRequest $request, HttpKernel $kernel = null)
        {

            $kernel = $kernel ?? $this->newKernel();

            ob_start();
            $this->runKernel($request, $kernel);
            return ob_get_clean();

        }

        protected function runAndAssertOutput($expected, IncomingRequest $request)
        {

            $this->assertSame(
                $expected,
                $actual = $this->runKernelAndGetOutput($request),
                "Expected output:[{$expected}] Received:['{$actual}'].");


        }

        protected function runAndAssertEmptyOutput(IncomingRequest $request) {

            $this->runAndAssertOutput('', $request);

        }

        protected function conditions() : array {

            return array_merge(RoutingServiceProvider::CONDITION_TYPES , [

                'true'                 => \Tests\stubs\Conditions\TrueCondition::class,
                'false'                => \Tests\stubs\Conditions\FalseCondition::class,
                'maybe'                => \Tests\stubs\Conditions\MaybeCondition::class,
                'unique'               => \Tests\stubs\Conditions\UniqueCondition::class,
                'dependency_condition' => \Tests\stubs\Conditions\ConditionWithDependency::class,

            ]);


        }

        protected function webRequest($method, $path) : IncomingWebRequest
        {

            return new IncomingWebRequest('wordpress.php', TestRequest::from($method, $path));

        }

    }