<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Contracts\ContainerAdapter;
    use Tests\fixtures\Middleware\BarMiddleware;
    use Tests\fixtures\Middleware\BazMiddleware;
    use Tests\fixtures\Middleware\FooBarMiddleware;
    use Tests\fixtures\Middleware\FooMiddleware;
    use Tests\stubs\TestRequest;
    use Snicco\View\MethodField;
    use Snicco\Contracts\AbstractRouteCollection;
    use Snicco\Contracts\ErrorHandlerInterface;
    use Snicco\Events\IncomingRequest;
    use Snicco\Events\IncomingWebRequest;
    use Snicco\ExceptionHandling\NullErrorHandler;
    use Snicco\Factories\ConditionFactory;
    use Snicco\Factories\RouteActionFactory;
    use Snicco\Http\HttpKernel;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\ResponseFactory;
    use Snicco\Middleware\Core\RouteRunner;
    use Snicco\Middleware\MiddlewareStack;
    use Snicco\Routing\FastRoute\FastRouteUrlGenerator;
    use Snicco\Routing\Pipeline;
    use Snicco\Routing\RouteCollection;
    use Snicco\Routing\Router;
    use Snicco\Routing\UrlGenerator;
    use Snicco\Routing\RoutingServiceProvider;

    trait CreateTestSubjects
    {

        use CreateUrlGenerator;
        use CreateRouteCollection;
        use CreateRouteCollection;

        /** @var MiddlewareStack */
        protected $middleware_stack;



        protected function createRoutes(\Closure $routes, bool $force_trailing = false)
        {

            $this->routes = $this->newRouteCollection();

            $this->router = $this->newRouter($force_trailing);

            $routes();

            $this->router->loadRoutes();

        }

        protected function newRouter(bool $force_trailing = false) : Router
        {

            return new Router($this->container, $this->routes, $force_trailing);

        }

        protected function newKernel(array $with_middleware = []) :HttpKernel
        {

            $this->container->instance(ErrorHandlerInterface::class, $error_handler = new NullErrorHandler());
            $this->container->instance(AbstractRouteCollection::class, $this->routes);
            $this->container->instance(ResponseFactory::class, $factory = $this->createResponseFactory());
            $this->container->instance(ContainerAdapter::class, $this->container);
            $this->container->instance(MethodField::class, new MethodField(TEST_APP_KEY));


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

            $router_runner = new RouteRunner($factory, $this->container, new Pipeline($this->container, $error_handler), $middleware_stack);

            $this->container->instance(RouteRunner::class, $router_runner);
            $this->container->instance(MiddlewareStack::class, $middleware_stack);
            $this->middleware_stack = $middleware_stack;


            return new HttpKernel(new Pipeline($this->container, $error_handler));


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

                'true'                 => \Tests\fixtures\Conditions\TrueCondition::class,
                'false'                => \Tests\fixtures\Conditions\FalseCondition::class,
                'maybe'                => \Tests\fixtures\Conditions\MaybeCondition::class,
                'unique'               => \Tests\fixtures\Conditions\UniqueCondition::class,
                'dependency_condition' => \Tests\fixtures\Conditions\ConditionWithDependency::class,

            ]);


        }

        protected function webRequest($method, $path) : IncomingWebRequest
        {

            return new IncomingWebRequest(TestRequest::from($method, $path), 'wordpress.php');

        }

    }