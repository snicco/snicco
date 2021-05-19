<?php


    declare(strict_types = 1);


    namespace Tests\traits;

    use Contracts\ContainerAdapter;
    use PHPUnit\Framework\Assert;
    use Tests\stubs\TestErrorHandler;
    use Tests\stubs\TestViewService;
    use Tests\stubs\TestRequest;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Factories\HandlerFactory;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Http\HttpResponseFactory;
    use WPEmerge\Routing\FastRoute\FastRouteMatcher;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\RouteCompiler;
    use WPEmerge\Routing\Router;

    trait SetUpKernel
    {

        use CreatePsr17Factories;

        /**
         * @var HttpKernel
         */
        private $kernel;

        /**
         * @var Router
         */
        private $router;

        private function newRouter ( ContainerAdapter $c ) : Router
        {

            $handler_factory = new HandlerFactory( [], $c );
            $condition_factory = new ConditionFactory( [], $c );
            $routes =  new RouteCollection(
                new FastRouteMatcher(),
                new RouteCompiler($handler_factory, $condition_factory)
            );

            $c->instance(HandlerFactory::class, $handler_factory);
            $c->instance(ConditionFactory::class, $condition_factory);
            $c->instance(RouteCollection::class, $routes);
            $c->instance(ResponseFactory::class, $response = $this->responseFactory());

            return new Router(
                $c,
                $routes,
                $response
            );

        }

        private function newKernel ( Router $router, ContainerAdapter $c ) : HttpKernel
        {

            return new HttpKernel(
                $router,
                $c,
                new TestErrorHandler(),
            );

        }

        private function createIncomingWebRequest($method, $path) : IncomingWebRequest
        {

            $request = TestRequest::from($method, $path);
            $request_event = new IncomingWebRequest('wordpress.php', $request);
            $request_event->request->withType(IncomingWebRequest::class);

            return $request_event;

        }

        private function createIncomingAdminRequest($method, $path) : IncomingAdminRequest
        {

            $request = TestRequest::from($method, $path);
            $request_event = new IncomingAdminRequest($request);
            $request_event->request->withType(IncomingAdminRequest::class);

            return $request_event;

        }

        private function assertMiddlewareRunTimes(int $times, $class)
        {

            $this->assertSame(
                $times, $GLOBALS['test'][$class::run_times],
                'Middleware ['.$class.'] was supposed to run: '.$times.' times. Actual: '.$GLOBALS['test'][$class::run_times]
            );

        }

        private function runAndGetKernelOutput(IncomingRequest $request)
        {

            ob_start();
            $this->kernel->handle($request);
            return ob_get_clean();

        }

        private function assertNothingSent($output)
        {

            Assert::assertEmpty($output);

        }

        private function assertBodySent($expected, $output)
        {

            Assert::assertSame($expected, $output);

        }

        private function assertOutput ( $expected , string $output ) {

            $this->assertStringContainsString( $expected, $output );

        }


    }