<?php


	namespace Tests\integration\Routing;

	use SniccoAdapter\BaseContainerAdapter;
	use Tests\TestRequest;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Routing\ConditionFactory;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;

	trait SetUpRouter {

		/**
		 * @var \WPEmerge\Routing\Router
		 */
		private $router;

		protected function setUp() : void {

			parent::setUp();

			$this->newRouter();

			unset( $GLOBALS['test'] );

		}

		private function newRouterWith( \Closure $routes ) {

			$this->newRouter();

			$routes( $this->router );

		}

		private function newRouter() {

			$conditions = is_callable([$this, 'conditions']) ? $this->conditions() : [];

			$container         = new BaseContainerAdapter();
			$condition_factory = new ConditionFactory( $conditions, $container );
			$handler_factory   = new HandlerFactory( [], $container );
			$route_collection  = new RouteCollection(
				$condition_factory,
				$handler_factory,
				new FastRouteMatcher()
			);
			$this->router      = new Router( $container, $route_collection );

		}

		protected function tearDown() : void {


			parent::tearDown();

			unset( $GLOBALS['test'] );
		}

		private function request( $method, $path ) : TestRequest {

			return TestRequest::from( $method, $path );

		}

		private function seeResponse( $expected, $response ) {

			$this->assertSame( $expected, $response );

		}

	}