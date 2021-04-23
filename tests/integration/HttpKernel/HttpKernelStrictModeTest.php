<?php


	namespace Tests\integration\HttpKernel;

	use Codeception\TestCase\WPTestCase;
	use Tests\integration\MockSubstituteBindings;
	use Tests\integration\SetUpTestApp;
	use Tests\MockRequest;
	use Tests\stubs\Middleware\FooMiddleware;
	use Tests\stubs\Middleware\GlobalFooMiddleware;
	use Tests\stubs\TestApp;
	use Tests\stubs\TestResponseService;
	use WPEmerge\Requests\Request;
	use Mockery as m;

	class HttpKernelStrictModeTest extends WPTestCase {

		use SetUpTestApp;
		use MockRequest;
		use MockSubstituteBindings;

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

			$this->disableGlobalMiddleware();

			$this->kernel = TestApp::resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );

			$GLOBALS['global_middleware_executed_times'] = 0;

		}

		protected function tearDown() : void {

			m::close();
			parent::tearDown();

			TestApp::setApplication( null );

			unset($GLOBALS['global_middleware_resolved_from_container']);
			unset($GLOBALS['route_middleware_resolved']);

		}


		/** @test */
		public function the_kernel_does_not_run_any_global_middleware_by_default_for_non_matching_routes () {

			$this->assertEquals(0 , $GLOBALS['global_middleware_executed_times'] );

			TestApp::route()
			       ->get()
			       ->url( '/web' )
			       ->handle( 'WebController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/non-existing-url' );

			$this->kernel->handle( $this->request );

			$this->assertNull(  $this->response_service->header_response );
			$this->assertNull(  $this->response_service->body_response );

			$this->assertEquals(0 , $GLOBALS['global_middleware_executed_times'] );


		}

		/** @test */
		public function in_strict_mode_global_middleware_will_always_be_executed_even_tho_no_routes_match () {

			$this->assertEquals(0 , $GLOBALS['global_middleware_executed_times'] );

			TestApp::route()
			       ->get()
			       ->url( '/web' )
			       ->handle( 'WebController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/non-existing-url' );

			$this->kernel->handle( $this->request );

			$this->assertNull(  $this->response_service->header_response );
			$this->assertNull(  $this->response_service->body_response );

			$this->assertEquals(0 , $GLOBALS['global_middleware_executed_times'] );


		}


		public function config() : array {

			return array_merge(TEST_CONFIG ,[


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

			]);

		}

	}