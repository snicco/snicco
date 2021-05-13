<?php


    declare(strict_types = 1);


    namespace Tests\integration\HttpKernel;

    use PHPUnit\Framework\Assert;
    use SniccoAdapter\BaseContainerAdapter;
    use Tests\CreateContainer;
    use Tests\SetUpDefaultMocks;
    use Tests\stubs\TestErrorHandler;
    use Tests\TestRequest;
    use Tests\CreateResponseFactory;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\HandlerFactory;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\FastRoute\FastRouteMatcher;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\Router;
    use WPEmerge\View\ViewService;

    trait SetUpKernel
    {

        use SetUpDefaultMocks;
        use CreateResponseFactory;
        use CreateContainer;


        /** @var \WPEmerge\Http\HttpKernel */
        private $kernel;

        /**
         * @var \WPEmerge\Routing\Router
         */
        private $router;

        /** @var \Contracts\ContainerAdapter */
        private $container;

        protected function setUp() : void
        {

            parent::setUp();

            $container = $this->createContainer();
            $handler_factory = new HandlerFactory([], $container);
            $condition_factory = new ConditionFactory([], $container);
            $error_handler = new TestErrorHandler();
            $router = new Router(
                $container,
                new RouteCollection(
                    $condition_factory,
                    $handler_factory,
                    new FastRouteMatcher()
                ),
                new ResponseFactory(\Mockery::mock(ViewService::class), $this->createFactory())
            );
            $this->router = $router;
            $this->container = $container;
            $this->kernel = new HttpKernel(
                $router,
                $container,
                $error_handler,
            );

            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($container);


        }

        private function createIncomingWebRequest($method, $path) : IncomingWebRequest
        {

            $request = TestRequest::from($method, $path);
            $request_event = new IncomingWebRequest('wordpress.php', $request);
            $request_event->request->setType(IncomingWebRequest::class);

            return $request_event;

        }

        private function createIncomingAdminRequest($method, $path) : IncomingAdminRequest
        {

            $request = TestRequest::from($method, $path);
            $request_event = new IncomingAdminRequest($request);
            $request_event->request->setType(IncomingAdminRequest::class);

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

    }