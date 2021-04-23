<?php


	namespace Tests\integration\HttpKernel;

	use Codeception\TestCase\WPTestCase;
	use Mockery as m;
	use Tests\integration\DisableMiddleware;
	use Tests\integration\SetUpTestApp;
	use Tests\MockRequest;
	use Tests\stubs\Middleware\FooMiddleware;
	use Tests\stubs\Middleware\GlobalFooMiddleware;
	use Tests\stubs\TestResponseService;
	use WPEmerge\Middleware\SubstituteBindings;
	use WPEmerge\Requests\Request;
	use Tests\stubs\IntegrationTestErrorHandler;
	use Tests\stubs\TestApp;



	/**
	 * @covers \WPEmerge\Kernels\HttpKernel
	 */
	class HttpKernelMiddlewareIntegrationTest extends WPTestCase {

		use SetUpTestApp;
		use MockRequest;


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

			$this->createMockWebRequest();

			$this->response_service = new TestResponseService();

			$this->bootstrapTestApp();


			$this->kernel = TestApp::resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );

			$GLOBALS['global_middleware_executed_times'] = 0;
		}

		protected function tearDown() : void {

			m::close();
			parent::tearDown();

			TestApp::setApplication( null );

			unset($GLOBALS['global_middleware_executed_times']);
			unset($GLOBALS['route_middleware_resolved']);

		}


		/** @test */
		public function route_middleware_is_auto_resolved_from_the_service_container() {


			$this->assertFalse(isset($GLOBALS['route_middleware_resolved']));

			TestApp::route()
			       ->get()
			       ->middleware(FooMiddleware::class)
			       ->url( '/' )
			       ->handle( 'WebController@request');


			$this->request
				->shouldReceive( 'getUrl' )
				->andReturn( 'https://wpemerge.test/' );


			$this->kernel->handle( $this->request );

			$this->assertSame( 'foo:foo_web_controller', $this->responseBody() );

			$this->assertTrue($GLOBALS['route_middleware_resolved']);

		}

		/** @test */
		public function route_middleware_arguments_can_be_passed() {

			TestApp::route()
			       ->get()
			       ->middleware(FooMiddleware::class . ':bar')
			       ->url( '/' )
			       ->handle( 'WebController@request');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/' );

			$this->kernel->handle($this->request);

			$this->assertSame( 'foobar:foo_web_controller', $this->responseBody() );

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

			$this->kernel->handle($this->request);

			$this->assertSame( 'foo:foo_admin_controller_dependency', $this->responseBody() );



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

			$this->kernel->handle($this->request);

			$this->assertSame( 'foo:foo_admin_controller_dependency', $this->responseBody() );
			$this->assertSame( 1, $GLOBALS['controller_constructor_count']);

			unset($GLOBALS['controller_constructor_count']);


		}

		/** @test */
		public function global_middleware_gets_resolved_from_the_service_container() {


			$this->assertSame(0 ,($GLOBALS['global_middleware_executed_times']));

			TestApp::route()
			       ->get()
			       ->middleware(FooMiddleware::class)
			       ->url( '/' )
			       ->handle( 'WebController@request');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/' );

			$this->kernel->handle($this->request);

			$this->assertSame( 'foo:foo_web_controller', $this->responseBody() );

			$this->assertSame(1, $GLOBALS['global_middleware_executed_times']);


		}

		/** @test */
		public function the_substitute_model_bindings_middleware_is_bound_in_the_container_correctly() {

			TestApp::setApplication(null);

			TestApp::make()->bootstrap(TEST_CONFIG);


			$config = TestApp::resolve( WPEMERGE_CONFIG_KEY );

			$this->assertContains(
				SubstituteBindings::class,
				$config['middleware_groups']['global']
			);



		}


		private function bootstrapTestApp() {

			TestApp::make()->bootstrap( array_merge(TEST_CONFIG,$this->config()) , false );
			TestApp::container()[ WPEMERGE_REQUEST_KEY ]                  = $this->request;
			TestApp::container()[ WPEMERGE_RESPONSE_SERVICE_KEY ]         = $this->response_service;
			TestApp::container()[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ] = new IntegrationTestErrorHandler();

		}

		public function config() : array {

			return array_merge(TEST_CONFIG ,[


				'middleware' => [

					'foo'  => FooMiddleware::class,
					'foo_global' => GlobalFooMiddleware::class,
				],

				'middleware_groups' => [
					'global' => [GlobalFooMiddleware::class],
				],

				'middleware_priority' => [
					// Examples:
					GlobalFooMiddleware::class,
				],

			]);


		}

	}


