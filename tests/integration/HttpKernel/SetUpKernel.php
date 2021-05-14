<?php


    declare(strict_types = 1);


    namespace Tests\integration\HttpKernel;

    use Contracts\ContainerAdapter;
    use PHPUnit\Framework\Assert;
    use Tests\CreateContainer;
    use Tests\SetUpDefaultMocks;
    use Tests\stubs\TestErrorHandler;
    use Tests\stubs\TestViewService;
    use Tests\TestRequest;
    use Tests\CreatePsr17Factories;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\HandlerFactory;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Http\HttpResponseFactory;
    use WPEmerge\Routing\FastRoute\FastRouteMatcher;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\Router;

    trait SetUpKernel
    {

        use SetUpDefaultMocks;
        use CreatePsr17Factories;
        use CreateContainer;


        /** @var HttpKernel */
        private $kernel;

        /**
         * @var Router
         */
        private $router;

        protected function setUp() : void
        {

            parent::setUp();

            $container = $this->createContainer();
            $handler_factory = new HandlerFactory( [], $container );
            $condition_factory = new ConditionFactory( [], $container );
            $error_handler = new TestErrorHandler();
            $router = new Router(
                $container,
                new RouteCollection(
                    $condition_factory,
                    $handler_factory,
                    new FastRouteMatcher()
                ),
                new HttpResponseFactory( new TestViewService(), $this->psrResponseFactory(), $this->psrStreamFactory() )
            );
            $this->router = $router;
            $container1 = $container;
            $this->kernel = new HttpKernel(
                $router,
                $container,
                $error_handler,
            );

            ApplicationEvent::make($container1);
            ApplicationEvent::fake();
            WP::setFacadeContainer($container);


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