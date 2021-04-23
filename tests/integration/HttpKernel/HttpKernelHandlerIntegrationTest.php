<?php


	namespace Tests\integration\HttpKernel;

	use Codeception\TestCase\WPTestCase;
	use Mockery as m;
	use Tests\integration\MockSubstituteBindings;
	use Tests\integration\SetUpTestApp;
	use Tests\MockRequest;
	use Tests\stubs\Controllers\Web\DependencyController;
	use Tests\stubs\Controllers\Web\TeamsController;
	use Tests\stubs\TestResponseService;
	use WPEmerge\Requests\Request;
	use Tests\stubs\TestApp;

	use const TEST_CONFIG;
	use const WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY;

	/**
	 * @covers \WPEmerge\Kernels\HttpKernel
	 */
	class HttpKernelHandlerIntegrationTest extends WPTestCase {

		use MockSubstituteBindings;
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

			$this->disableGlobalMiddleware();

			$this->kernel = TestApp::resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );

		}

		protected function tearDown() : void {

			m::close();
			parent::tearDown();

			TestApp::setApplication( null );

		}

		/** @test */
		public function a_class_handler_is_auto_resolved_from_the_service_container() {

			TestApp::route()->get()->url( '/' )
			       ->handle( DependencyController::class . '@handle' );

			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/' );

			$this->kernel->handle( $this->request );

			$this->assertResponseSend();

			$this->assertSame( 'foo_web_controller', $this->responseBody() );

		}

		/** @test */
		public function a_class_handler_gets_route_arguments_passed_to_the_method() {

			TestApp::route()
			       ->get()
			       ->url( '/teams/{team}' )
			       ->handle( TeamsController::class . '@noTypeHint' );


			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/teams/dortmund' );

			$this->kernel->handle( $this->request );

			$this->assertResponseSend();

			$this->assertSame( 'dortmund', $this->responseBody()  );


		}

		/** @test */
		public function a_method_dependency_gets_resolved_from_the_service_container() {

			TestApp::route()
			       ->get()
			       ->url( '/' )
			       ->handle( DependencyController::class . '@withMethodDependency' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/' );


			$this->kernel->handle( $this->request );

			$this->assertResponseSend();

			$this->assertSame( 'foobar', $this->responseBody() );

		}

		/** @test */
		public function web_controllers_can_be_resolved_without_the_full_namespace () {

			TestApp::route()
			       ->get()
			       ->url( '/web' )
			       ->handle( 'WebController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/web' );

			$this->kernel->handle( $this->request );

			$this->assertResponseSend();

			$this->assertSame( 'web_controller', $this->responseBody() );


		}

		/** @test */
		public function admin_controllers_can_be_resolved_without_the_full_namespace () {

			TestApp::route()
			       ->get()
			       ->url( '/admin' )
			       ->handle( 'AdminController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/admin' );

			$this->kernel->handle( $this->request );

			$this->assertResponseSend();

			$this->assertSame( 'admin_controller', $this->responseBody() );


		}

		/** @test */
		public function ajax_controllers_can_be_resolved_without_the_full_namespace() {

			TestApp::route()
			       ->get()
			       ->url( '/ajax' )
			       ->handle( 'AjaxController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/ajax' );

			$this->kernel->handle( $this->request );

			$this->assertResponseSend();

			$this->assertSame( 'ajax_controller', $this->responseBody() );

		}

		public function config() : array {

			return TEST_CONFIG;

		}

	}


