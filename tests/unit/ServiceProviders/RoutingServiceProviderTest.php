<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Codeception\TestCase\WPTestCase;
	use Tests\stubs\Conditions\TrueCondition;
	use Tests\stubs\CreatesAdminPages;
	use Tests\stubs\TestApp;
	use Tests\TestRequest;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteMatcher;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;

	class RoutingServiceProviderTest extends WPTestCase {

		use BootApplication;
		use CreatesAdminPages;

		protected function tearDown() : void {

			$GLOBALS['wp_filter']['wp_doing_ajax'] = [];


			parent::tearDown();
		}

		/** @test */
		public function all_conditions_are_loaded() {

			$app = $this->bootNewApplication( [
				'routing' => [
					'conditions' => [
						'true' => TrueCondition::class,
					],
				],
			] );

			$conditions = $app->config( 'routing.conditions', [] );

			$this->assertArrayHasKey( 'custom', $conditions );
			$this->assertArrayHasKey( 'negate', $conditions );
			$this->assertArrayHasKey( 'post_id', $conditions );
			$this->assertArrayHasKey( 'post_slug', $conditions );
			$this->assertArrayHasKey( 'post_status', $conditions );
			$this->assertArrayHasKey( 'post_template', $conditions );
			$this->assertArrayHasKey( 'post_type', $conditions );
			$this->assertArrayHasKey( 'ajax', $conditions );
			$this->assertArrayHasKey( 'admin', $conditions );
			$this->assertArrayHasKey( 'true', $conditions );
			$this->assertArrayHasKey( 'query_string', $conditions );


		}

		/** @test */
		public function without_caching_a_fast_route_matcher_is_returned() {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf( FastRouteMatcher::class, $app->resolve( RouteMatcher::class ) );


		}

		/** @test */
		public function an_exception_gets_thrown_if_a_cache_file_path_is_missing() {

			$this->expectExceptionMessage( 'No cache file provided:' );

			$this->bootNewApplication( [
				'routing' => [
					'cache' => true,
				],
			] )->resolve( RouteMatcher::class );


		}

		/** @test */
		public function a_cached_route_matcher_can_be_configured() {

			$app = $this->bootNewApplication( [
				'routing' => [
					'cache'      => true,
					'cache_file' => TESTS_DIR . '_data' . DS . 'tests.route.cache.php',
				],
			] );

			$matcher = $app->resolve( RouteMatcher::class );

			$this->assertInstanceOf( CachedFastRouteMatcher::class, $matcher );

		}

		/** @test */
		public function the_router_is_loaded_correctly() {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf( Router::class, $app->resolve( Router::class ) );

		}

		/** @test */
		public function the_condition_factory_can_be_loaded() {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf( ConditionFactory::class, $app->resolve( ConditionFactory::class ) );

		}

		/** @test */
		public function ajax_routes_are_loaded_for_ajax_request() {

			add_filter( 'wp_doing_ajax', function () {

				return true;

			} );

			$app = $this->bootNewApplication( $this->routeConfig() );

			/** @var Router $router */
			$router = $app->resolve( Router::class );

			$router->middlewareGroup( 'ajax', [] );

			$request = TestRequest::from( 'POST', 'foo' );
			$request->request->set( 'action', 'test' );

			$response = $router->runRoute( $request );

			$this->assertSame( 'foo', $response );

		}

		/** @test */
		public function admin_routes_are_loaded_for_admin_requests() {


			$app = $this->bootNewApplication( $this->routeConfig() );

			/** @var Router $router */
			$router = $app->resolve( Router::class );
			$router->isAdmin();


			$router->middlewareGroup( 'admin', [] );

			$request = TestRequest::from( 'GET', 'foo' );

			$response = $router->runRoute( $request );

			$this->assertSame( 'foo', $response );

		}

		/** @test */
		public function admin_routes_have_the_admin_preset_applied() {

			$url = $this->urlTo( 'bar' );

			$app = $this->bootNewApplication( $this->routeConfig() );

			/** @var Router $router */
			$router = $app->resolve( Router::class );

			$router->middlewareGroup( 'admin', [] );

			$request = TestRequest::fromFullUrl( 'GET', $url );

			$this->assertSame( 'BAR', $router->runRoute( $request ) );

		}

		/** @test */
		public function web_routes_are_loaded_by_default() {

			$app = $this->bootNewApplication( $this->routeConfig() );

			/** @var Router $router */
			$router = $app->resolve( Router::class );

			$router->middlewareGroup( 'web', [] );

			$request = TestRequest::from( 'GET', 'foo' );

			$response = $router->runRoute( $request );

			$this->assertSame( 'foo', $response );

		}


		private function routeConfig() : array {

			return [
				'routing' => [

					'definitions' => TESTS_DIR . DS . 'stubs' . DS . 'Routes',

				],
			];

		}

	}
