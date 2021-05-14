<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use SniccoAdapter\BaseContainerAdapter;
    use Tests\AssertsResponse;
    use Tests\CreatePsr17Factories;
    use Tests\SetUpDefaultMocks;
	use Tests\Test;
	use Tests\TestRequest;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;
	use WpFacade\WpFacade;

	class RouteCachingTest extends Test {

		use SetUpDefaultMocks;
        use CreatePsr17Factories;
        use AssertsResponse;

		/**
		 * @var Router
		 */
		private $router;

		private $cache_file;


		protected function afterSetUp () {

			$this->cache_file = TESTS_DIR . DS . '_data' . DS . 'route.cache.php';

			$this->newCachedRouter();

			$this->assertFalse( file_exists( $this->cache_file ) );


		}

		private function newCachedRouter( $file = null ) : Router {


			$container         = new BaseContainerAdapter();
			$condition_factory = new ConditionFactory( [], $container );
			$handler_factory   = new HandlerFactory( [], $container );
			$route_collection  = new RouteCollection(
				$condition_factory,
				$handler_factory,
				new CachedFastRouteMatcher( new FastRouteMatcher(), $file ?? $this->cache_file )
			);

			WpFacade::setFacadeContainer($container);

			return $this->router = new Router(
			    $container,
                $route_collection,
                $this->responseFactory()
            );

		}

		protected function beforeTearDown() : void {

			if ( file_exists( $this->cache_file ) ) {

				unlink( $this->cache_file );
			}


		}

		/** @test */
		public function a_cache_file_gets_created_when_running_the_router_for_the_first_time() {

			$this->router->get( 'foo', Controller::class . '@handle' );
			$this->router->get( 'bar', Controller::class . '@handle' );
			$this->router->get( 'baz', Controller::class . '@handle' );
			$this->router->get( 'biz', Controller::class . '@handle' );
			$this->router->get( 'boo', Controller::class . '@handle' );

			$this->assertFalse( file_exists( $this->cache_file ) );

			$response = $this->router->runRoute( TestRequest::from( 'GET', 'foo' ) );

			$this->assertOutput( 'foo', $response );

			$this->assertTrue( file_exists( $this->cache_file ) );


		}

		/** @test */
		public function routes_can_be_read_from_the_cache_without_needing_to_define_them() {

			$this->router->get( 'foo', Controller::class . '@handle' );
			$this->router->get( 'bar', Controller::class . '@handle' );
			$this->router->get( 'baz', Controller::class . '@handle' );
			$this->router->get( 'biz', Controller::class . '@handle' );
			$this->router->get( 'boo', Controller::class . '@handle' );
			$response = $this->router->runRoute( TestRequest::from( 'GET', 'foo' ) );
			$this->assertOutput( 'foo', $response );

			$router = $this->newCachedRouter( $this->cache_file );

			$response = $router->runRoute( TestRequest::from( 'GET', 'foo' ) );
			$this->assertOutput( 'foo', $response );

			$response = $router->runRoute( TestRequest::from( 'GET', 'bar' ) );
			$this->assertOutput( 'foo', $response );

			$response = $router->runRoute( TestRequest::from( 'GET', 'biz' ) );
			$this->assertOutput( 'foo', $response );

			$response = $router->runRoute( TestRequest::from( 'GET', 'baz' ) );
			$this->assertOutput( 'foo', $response );

			$response = $router->runRoute( TestRequest::from( 'GET', 'boo' ) );
			$this->assertOutput( 'foo', $response );

			$router->get( '/foobar', Controller::class . '@handle' );
			$response = $router->runRoute( TestRequest::from( 'GET', 'foobar' ) );
			$this->assertNullResponse(  $response );

		}

		/** @test */
		public function a_cache_file_gets_created_for_routes_with_closure_handlers() {

			$class = new Controller();

			$this->router->get( 'foo', function () use ( $class ) {

				return $class->handle();

			} );

			$this->assertFalse( file_exists( $this->cache_file ) );

			$response = $this->router->runRoute( TestRequest::from( 'GET', 'foo' ) );

			$this->assertOutput( 'foo', $response );

			$this->assertTrue( file_exists( $this->cache_file ) );

		}

		/** @test */
		public function closure_handlers_are_read_correctly_from_the_cache_file() {

			$class = new Controller();

			$this->router->get( 'foo', function () use ( $class ) {

				return $class->handle();

			} );

			$response = $this->router->runRoute( TestRequest::from( 'GET', 'foo' ) );
			$this->assertOutput( 'foo', $response );

			$router = $this->newCachedRouter( $this->cache_file );

			$response = $router->runRoute( TestRequest::from( 'GET', 'foo' ) );
			$this->assertOutput( 'foo', $response );


		}

	}


	class Controller {


		public function handle() {

			return 'foo';

		}

	}