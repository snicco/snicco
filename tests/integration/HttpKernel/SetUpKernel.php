<?php


	namespace Tests\integration\HttpKernel;

	use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use BetterWpHooks\ListenerFactory;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\TestErrorHandler;
	use Tests\stubs\TestResponseService;
	use Tests\TestRequest;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\Factories\ConditionFactory;
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
			$response_service  = new TestResponseService();
			$router            = new Router(
				$container,
				new RouteCollection(
					$condition_factory,
					$handler_factory,
					new FastRouteMatcher() )
			);

			$this->router           = $router;
			$this->response_service = $response_service;
			$this->container        = $container;
			$this->kernel           = new HttpKernel( $response_service, $router, $container, $error_handler );

			ApplicationEvent::make($this->container);
			ApplicationEvent::fake();

			$GLOBALS['test'] = [];

		}

		protected function tearDown() : void {

			parent::tearDown();

			$GLOBALS['test'] = [];

		}

		private function createIncomingWebRequest( $method, $path ) : IncomingWebRequest {

			$request_event          = new IncomingWebRequest( 'wordpress.php' );
			$request_event->request = TestRequest::from( $method, $path );
			$request_event->request->setType(IncomingWebRequest::class);

			return $request_event;

		}

		private function createIncomingAdminRequest( $method, $path ) : IncomingAdminRequest {

			$request_event          = new IncomingAdminRequest();
			$request_event->request = TestRequest::from( $method, $path );
			$request_event->request->setType(IncomingAdminRequest::class);

			return $request_event;

		}

		private function assertMiddlewareRunTimes( int $times, $class ) {

			$this->assertSame(
				$times, $GLOBALS['test'][ $class::run_times ],
				'Middleware [' . $class . '] was supposed to run: ' . $times . ' times. Actual: ' . $GLOBALS['test'][ $class::run_times ]
			);

		}


	}