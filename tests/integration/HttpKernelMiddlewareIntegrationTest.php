<?php


	namespace Tests\integration;

	use Codeception\TestCase\WPTestCase;
	use GuzzleHttp\Psr7\Response as Psr7Response;
	use GuzzleHttp\Psr7\Utils;
	use Mockery as m;
	use Tests\Laravel\App;
	use Tests\stubs\Middleware\FooMiddleware;
	use Tests\stubs\Middleware\GlobalFooMiddleware;
	use WPEmerge\Middleware\SubstituteModelBindings;
	use WPEmerge\Requests\Request;
	use WPEmerge\Responses\ResponseService;
	use Tests\stubs\Handlers\ClassHandlerConstructorDependency;
	use Tests\stubs\IntegrationTestErrorHandler;
	use Tests\stubs\TestApp;

	/**
	 * @covers \WPEmerge\Kernels\HttpKernel
	 */
	class HttpKernelMiddlewareIntegrationTest extends WPTestCase {

		use DisableGlobalMiddleWare;

		/**
		 * @var \WPEmerge\Kernels\HttpKernel
		 */
		private $kernel;

		/** @var \WPEmerge\Requests\Request */
		private $request;

		/** @var \WPEmerge\Responses\ResponseService */
		private $response_service;

		protected function setUp() : void {

			parent::setUp();

			$this->request = m::mock( Request::class );
			$this->request->shouldReceive( 'getMethod' )->andReturn( 'GET' );

			$this->response_service = m::mock( ResponseService::class );

			$this->bootstrapTestApp();

			$this->disableGlobalMiddleware();

			$this->kernel = TestApp::resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );

		}


		protected function tearDown() : void {

			m::close();
			parent::tearDown();

			TestApp::setApplication( null );

			unset($GLOBALS['global_middleware_resolved_from_container']);

		}

		/** @test */
		public function middleware_is_auto_resolved_from_the_service_container() {

			TestApp::route()
			       ->get()
			       ->middleware(FooMiddleware::class)
			       ->url( '/' )
			       ->handle( 'WebController@request');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/' );

			/** @var \Tests\stubs\TestResponse $response */
			$response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'foo:foo_dependency_web_controller', $response->body() );

		}

		/** @test */
		public function middleware_arguments_can_be_passed() {

			TestApp::route()
			       ->get()
			       ->middleware(FooMiddleware::class . ':bar')
			       ->url( '/' )
			       ->handle( 'WebController@request');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/' );

			/** @var \Tests\stubs\TestResponse $response */
			$response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'foobar:foo_dependency_web_controller', $response->body() );

		}

		/** @test */
		public function controller_middleware_gets_resolved_from_the_service_container () {

			TestApp::route()
			       ->get()
			       ->url( '/wp-admin/dashboard' )
			       ->handle( 'AdminControllerWithMiddleware@handle');

			$this->request
				->shouldReceive( 'getUrl' )
				->andReturn( 'https://wpemerge.test/wp-admin/dashboard' );

			/** @var \Tests\stubs\TestResponse $response */
			$response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'foo:foo_dependency_admin_controller_dependency', $response->body() );



		}

		/** @test */
		public function when_a_controller_uses_controller_middleware_it_only_ever_gets_resolved_once() {

			$GLOBALS['controller_constructor_count'] = 0;

			TestApp::route()
			       ->get()
			       ->url( '/wp-admin/dashboard' )
			       ->handle( 'AdminControllerWithMiddleware@handle');

			$this->request
				->shouldReceive( 'getUrl' )
				->andReturn( 'https://wpemerge.test/wp-admin/dashboard' );

			/** @var \Tests\stubs\TestResponse $response */
			$response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'foo:foo_dependency_admin_controller_dependency', $response->body() );
			$this->assertSame( 1, $GLOBALS['controller_constructor_count']);

			unset($GLOBALS['controller_constructor_count']);

		}

		/** @test */
		public function global_middleware_gets_resolved_from_the_service_container() {


			$this->assertFalse(isset($GLOBALS['global_middleware_resolved_from_container']));

			TestApp::route()
			       ->get()
			       ->middleware(FooMiddleware::class)
			       ->url( '/' )
			       ->handle( 'WebController@request');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/' );

			/** @var \Tests\stubs\TestResponse $response */
			$response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'foo:foo_dependency_web_controller', $response->body() );

			$this->assertTrue($GLOBALS['global_middleware_resolved_from_container']);


		}

		/** @test */
		public function the_substitute_model_bindings_middleware_is_bound_in_the_container_correctly() {

			$bound = TestApp::container()->offsetExists(SubstituteModelBindings::class);

			$this->assertTrue($bound);

			$config = TestApp::resolve( WPEMERGE_CONFIG_KEY );

			$this->assertContains(
				SubstituteModelBindings::class,
				$config['middleware_groups']['global']
			);

			$this->assertSame([

				SubstituteModelBindings::class,
				GlobalFooMiddleware::class,

			], $config['middleware_priority']);

		}

		/** @test */
		public function foo() {

			TestApp::route()
			       ->get()
			       ->middleware(FooMiddleware::class)
			       ->url( '/' )
			       ->handle( 'WebController@request');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/' );

			/** @var \Tests\stubs\TestResponse $response */
			$response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'foo:foo_dependency_web_controller', $response->body() );

		}



		private function bootstrapTestApp() {

			TestApp::make()->bootstrap( array_merge(TEST_CONFIG,$this->config()) , false );
			TestApp::container()[ WPEMERGE_REQUEST_KEY ]                  = $this->request;
			TestApp::container()[ WPEMERGE_RESPONSE_SERVICE_KEY ]         = $this->response_service;
			TestApp::container()[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ] = new IntegrationTestErrorHandler();

		}

		private function config() : array {

			return [


				'middleware' => [

					'foo'  => FooMiddleware::class,
					'foo_global' => GlobalFooMiddleware::class,
				],

				'middleware_groups' => [
					'global' => ['foo_global'],
				],

				'middleware_priority' => [
					// Examples:
					GlobalFooMiddleware::class,
				],

			];


		}

	}


