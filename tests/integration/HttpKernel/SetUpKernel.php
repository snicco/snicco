<?php


	declare( strict_types = 1 );


	namespace Tests\integration\HttpKernel;

	use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use BetterWpHooks\ListenerFactory;
	use PHPUnit\Framework\Assert;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\TestErrorHandler;
	use Tests\stubs\TestResponseService;
	use Tests\TestRequest;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Http\ResponseService;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;

	trait SetUpKernel {

		/** @var \WPEmerge\Http\HttpKernel */
		private $kernel;

		/**
		 * @var \WPEmerge\Routing\Router
		 */
		private $router;

		/** @var TestResponseService */
		private $response_service;

		/** @var \Contracts\ContainerAdapter */
		private $container;

		protected function setUp() : void {

			parent::setUp();

			$container         = new BaseContainerAdapter();
			$handler_factory   = new HandlerFactory( [], $container );
			$condition_factory = new ConditionFactory( [], $container );
			$error_handler     = new TestErrorHandler();
			$router            = new Router(
				$container,
				new RouteCollection(
					$condition_factory,
					$handler_factory,
					new FastRouteMatcher() )
			);

			$this->router           = $router;
			$this->container        = $container;
			$this->kernel           = new HttpKernel( $router, $container, $error_handler );

			ApplicationEvent::make($this->container);
			ApplicationEvent::fake();

			$GLOBALS['test'] = [];

		}

		protected function tearDown() : void {

			parent::tearDown();

			$GLOBALS['test'] = [];

		}

		private function createIncomingWebRequest( $method, $path ) : IncomingWebRequest {

			$request = TestRequest::from( $method, $path );
			$request_event          = new IncomingWebRequest( 'wordpress.php', $request );
			$request_event->request->setType(IncomingWebRequest::class);

			return $request_event;

		}

		private function createIncomingAdminRequest( $method, $path ) : IncomingAdminRequest {

			$request = TestRequest::from( $method, $path );
			$request_event          = new IncomingAdminRequest($request);
			$request_event->request->setType(IncomingAdminRequest::class);

			return $request_event;

		}

		private function assertMiddlewareRunTimes( int $times, $class ) {

			$this->assertSame(
				$times, $GLOBALS['test'][ $class::run_times ],
				'Middleware [' . $class . '] was supposed to run: ' . $times . ' times. Actual: ' . $GLOBALS['test'][ $class::run_times ]
			);

		}

		private function runAndGetKernelOutput (IncomingRequest $request) {

			ob_start();
			$this->kernel->handle($request);
			return ob_get_clean();
		}

		private function assertNothingSent($output) {

			Assert::assertEmpty($output);

		}

		private function assertBodySent($expected, $output) {

			Assert::assertSame($expected, $output);

		}

	}