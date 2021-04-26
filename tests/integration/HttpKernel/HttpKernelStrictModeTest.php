<?php


	namespace Tests\integration\HttpKernel;

	use BetterWpHooks\Contracts\Dispatcher;
	use Codeception\TestCase\WPTestCase;
	use Psr\Http\Message\ResponseInterface;
	use Tests\integration\SetUpTestApp;
	use Tests\MockRequest;
	use Tests\stubs\Middleware\FooMiddleware;
	use Tests\stubs\Middleware\GlobalFooMiddleware;
	use Tests\stubs\TestApp;
	use Tests\stubs\TestResponseService;
	use WPEmerge\Events\IncomingRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Requests\Request;
	use Mockery as m;

	class HttpKernelStrictModeTest extends WPTestCase {

		use SetUpTestApp;
		use MockRequest;

		/**
		 * @var \WPEmerge\Http\HttpKernel
		 */
		private $kernel;

		/** @var \WPEmerge\Requests\Request */
		private $request;

		/** @var \WPEmerge\Responses\ResponseService */
		private $response_service;

		/** @var \WPEmerge\Events\IncomingRequest  */
		private $request_event;

		protected function setUp() : void {

			parent::setUp();

			$this->request = m::mock( Request::class );
			$this->createMockWebRequest();

			$this->response_service = new TestResponseService();

			$this->request_event = m::mock(IncomingRequest::class);
			$this->request_event->request = $this->request;


			$this->bootstrapTestApp();


			$this->kernel = TestApp::resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );

			$GLOBALS['global_middleware_executed_times'] = 0;

		}

		protected function tearDown() : void {

			m::close();
			parent::tearDown();

			TestApp::setApplication( null );

			unset( $GLOBALS['global_middleware_resolved_from_container'] );
			unset( $GLOBALS['route_middleware_resolved'] );

		}


		/** @test */
		public function the_kernel_does_not_run_any_global_middleware_by_default_for_non_matching_routes() {

			$this->assertEquals( 0, $GLOBALS['global_middleware_executed_times'] );

			TestApp::route()
			       ->get()
			       ->url( '/web' )
			       ->handle( 'WebController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/non-existing-url' );

			$this->kernel->handle($this->request_event);

			$this->assertNull( $this->response_service->header_response );
			$this->assertNull( $this->response_service->body_response );

			$this->assertEquals( 0, $GLOBALS['global_middleware_executed_times'] );


		}

		/** @test */
		public function in_strict_mode_global_middleware_will_always_be_executed_even_tho_no_routes_match() {

			$this->assertEquals( 0, $GLOBALS['global_middleware_executed_times'] );

			TestApp::container()['strict.mode'] = true;

			TestApp::route()
			       ->get()
			       ->url( '/web' )
			       ->handle( 'WebController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/non-existing-url' );

			$this->request->shouldReceive('forceMatch')->once();

			$this->kernel->handle($this->request_event);

			$this->assertNull( $this->response_service->header_response );
			$this->assertNull( $this->response_service->body_response );

			$this->assertEquals( 1, $GLOBALS['global_middleware_executed_times'] );


		}

		/** @test */
		public function in_default_mode_global_middleware_is_executed_when_a_route_matches() {

			$this->assertEquals( 0, $GLOBALS['global_middleware_executed_times'] );

			TestApp::route()
			       ->get()
			       ->url( '/web' )
			       ->handle( 'WebController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/web' );

			$this->kernel->handle($this->request_event);

			$this->assertInstanceOf(ResponseInterface::class, $this->response_service->header_response );
			$this->assertSame( $this->response_service->body_response, $this->response_service->header_response  );

			$this->assertEquals( 1, $GLOBALS['global_middleware_executed_times'] );


		}

		/** @test */
		public function by_default_web_requests_without_matching_routes_will_return_wps_default_template() {

			/** @var \BetterWpHooks\Dispatchers\WordpressDispatcher $dispatcher */
			$dispatcher = TestApp::resolve( Dispatcher::class );

			$template = $dispatcher->dispatch( new IncomingWebRequest( 'wordpress.template.php' ) );

			$this->assertSame( 'wordpress.template.php', $template );


		}

		/** @test */
		public function in_strict_mode_web_requests_without_matching_routes_will_return_null_to_the_wp_template_include_filter() {

			/** @var \BetterWpHooks\Dispatchers\WordpressDispatcher $dispatcher */
			$dispatcher = TestApp::resolve( Dispatcher::class );

			TestApp::container()['strict.mode'] = true;

			$template = $dispatcher->dispatch( new IncomingWebRequest( 'wordpress.template.php' ) );

			$this->assertNull( $template );

		}

		/** @test */
		public function all_middleware_can_be_completely_disabled_for_testing () {

			TestApp::container()->instance('middleware.disable', true);

			$this->assertEquals( 0, $GLOBALS['global_middleware_executed_times'] );
			$this->assertFalse(isset($GLOBALS['route_middleware_resolved']));


			TestApp::route()
			       ->get()
					->middleware('foo')
			       ->url( '/web' )
			       ->handle( 'WebController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/web' );

			$this->request->shouldNotReceive('forceMatch');

			$this->kernel->handle($this->request_event);

			$this->assertInstanceOf(ResponseInterface::class, $this->response_service->header_response );
			$this->assertSame( $this->response_service->body_response, $this->response_service->header_response  );


			$this->assertEquals( 0, $GLOBALS['global_middleware_executed_times'] );
			$this->assertFalse(isset($GLOBALS['route_middleware_resolved']));

		}

		public function config() : array {

			return array_merge( TEST_CONFIG, [

				'middleware' => [

					'foo' => FooMiddleware::class,

				],

				'middleware_groups' => [
					'global' => [ GlobalFooMiddleware::class ],
					'web'    => [ 'foo' ],
				],

				'middleware_priority' => [
					// Examples:
					GlobalFooMiddleware::class,
				],

			] );

		}

	}