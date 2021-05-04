<?php


	namespace Tests\integration\HttpKernel;

	use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use BetterWpHooks\ListenerFactory;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\TestErrorHandler;
	use Tests\stubs\TestResponseService;
	use Tests\TestRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\Routing\ConditionFactory;
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

			$this->router = $router;
			$this->response_service = $response_service;
			$this->kernel = new HttpKernel( $response_service, $router, $container, $error_handler );

			$GLOBALS['test'] = [];

		}

		protected function tearDown() : void {

			parent::tearDown();

			$GLOBALS['test'] = [];

		}

		private function createIncomingWebRequest( $method, $path ) : IncomingWebRequest {

			$request_event          = new IncomingWebRequest( 'wordpress.php' );
			$request_event->request = TestRequest::from( $method, $path );

			return $request_event;

		}

		private function assertMiddlewareRunTimes(int $times, $class) {

			$this->assertSame(
				$times, $GLOBALS['test'][ $class::run_times ],
				'Middleware [' .$class . '] was supposed to run: ' . $times . ' times. Actual: ' .$GLOBALS['test'][ $class::run_times ]
			);

		}

		private function newDispatcher () :WordpressDispatcher {

			return new WordpressDispatcher(new ListenerFactory());

		}

	}