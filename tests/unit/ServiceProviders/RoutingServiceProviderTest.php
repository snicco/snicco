<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Tests\stubs\Conditions\TrueCondition;
	use Tests\stubs\CreatesAdminPages;
	use Tests\stubs\TestApp;
	use Tests\TestCase;
	use Tests\TestRequest;
	use WPEmerge\Contracts\RouteMatcher;
	use WPEmerge\Facade\WP;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\Router;
	use WPEmerge\ServiceProviders\AliasServiceProvider;
	use WPEmerge\ServiceProviders\FactoryServiceProvider;
	use WPEmerge\ServiceProviders\RoutingServiceProvider;

	class RoutingServiceProviderTest extends TestCase {

		use BootServiceProviders;
		use CreatesAdminPages;


		function neededProviders() : array {

			return  [
				RoutingServiceProvider::class,
				FactoryServiceProvider::class,
				AliasServiceProvider::class
			];

		}

		/** @test */
		public function all_conditions_are_loaded() {

			$this->config->set( 'routing.conditions.true', TrueCondition::class );

			$conditions = $this->config->get( 'routing.conditions' );

			$this->assertArrayHasKey( 'custom', $conditions );
			$this->assertArrayHasKey( 'negate', $conditions );
			$this->assertArrayHasKey( 'post_id', $conditions );
			$this->assertArrayHasKey( 'post_slug', $conditions );
			$this->assertArrayHasKey( 'post_status', $conditions );
			$this->assertArrayHasKey( 'post_template', $conditions );
			$this->assertArrayHasKey( 'post_type', $conditions );
			$this->assertArrayHasKey( 'ajax', $conditions );
			$this->assertArrayHasKey( 'admin', $conditions );
			$this->assertArrayHasKey( 'query_string', $conditions );
			$this->assertArrayHasKey( 'true', $conditions );


		}

		/** @test */
		public function without_caching_a_fast_route_matcher_is_returned() {

			$this->assertInstanceOf( FastRouteMatcher::class, TestApp::resolve( RouteMatcher::class ) );

		}

		/** @test */
		public function an_exception_gets_thrown_if_a_cache_file_path_is_missing() {

			$this->expectExceptionMessage( 'No cache file provided:' );

			$this->config->set( 'routing.cache', true );

			$this->app->resolve( RouteMatcher::class );


		}

		/** @test */
		public function a_cached_route_matcher_can_be_configured() {

			$this->config->set( 'routing.cache', true );
			$this->config->set( 'routing.cache_file', TESTS_DIR . '_data' . DS . 'tests.route.cache.php' );

			$matcher = $this->app->resolve( RouteMatcher::class );

			$this->assertInstanceOf( CachedFastRouteMatcher::class, $matcher );

		}

		/** @test */
		public function the_router_is_loaded_correctly() {

			$this->assertInstanceOf( Router::class, $this->app->resolve( Router::class ) );

		}

		/** @test */
		public function the_condition_factory_can_be_loaded() {


			$this->assertInstanceOf( ConditionFactory::class, $this->app->resolve( ConditionFactory::class ) );

		}

		/** @test */
		public function ajax_routes_are_loaded_for_ajax_request() {

			$this->config->set('routing.definitions', TESTS_DIR . DS . 'stubs' . DS . 'Routes');

			WP::shouldReceive('isAdminAjax')->andReturnTrue();
			WP::shouldReceive('isUserLoggedIn')->andReturnTrue();

			$this->boostrapProviders();

			/** @var Router $router */
			$router = $this->app->resolve( Router::class );

			$router->middlewareGroup( 'ajax', [] );

			$request = TestRequest::from( 'POST', 'foo' );
			$request->request->set( 'action', 'test' );

			$response = $router->runRoute( $request );

			$this->assertSame( 'foo', $response );

		}

		/** @test */
		public function admin_routes_are_loaded_for_admin_requests_and_have_the_correct_prefix_applied() {

			$this->config->set('routing.definitions', TESTS_DIR . DS . 'stubs' . DS . 'Routes');

			WP::shouldReceive('isAdminAjax')->andReturnFalse();
			WP::shouldReceive('isAdmin')->andReturnTrue();

			$this->boostrapProviders();

			/** @var Router $router */
			$router = $this->app->resolve( Router::class );

			$router->middlewareGroup( 'admin', [] );

			$request = TestRequest::fromFullUrl( 'GET', $this->urlTo( 'foo' ) );

			$response = $router->runRoute( $request );

			$this->assertSame( 'FOO', $response );

		}

		/** @test */
		public function web_routes_are_loaded_by_default() {

			$this->config->set('routing.definitions', TESTS_DIR . DS . 'stubs' . DS . 'Routes');
			$this->boostrapProviders();

			/** @var Router $router */
			$router = $this->app->resolve( Router::class );

			$router->middlewareGroup( 'web', [] );

			$request = TestRequest::from( 'GET', 'foo' );

			$response = $router->runRoute( $request );

			$this->assertSame( 'foo', $response );

		}




	}
