<?php


	namespace Tests\integration\HttpKernel;

	use Codeception\TestCase\WPTestCase;
	use Mockery as m;
	use Tests\integration\DisableMiddleware;
	use Tests\integration\SetUpTestApp;
	use Tests\stubs\Controllers\Web\DependencyController;
	use Tests\stubs\Controllers\Web\TeamsController;
	use Tests\stubs\TestResponseService;
	use Tests\TestRequest;
	use WPEmerge\Events\IncomingRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use Tests\stubs\TestApp;

	/**
	 * @covers \WPEmerge\Http\HttpKernel
	 */
	class HttpKernelHandlerIntegrationTest extends WPTestCase {

		use DisableMiddleware;
		use SetUpTestApp;

		/**
		 * @var \WPEmerge\Http\HttpKernel
		 */
		private $kernel;

		/** @var \WPEmerge\Requests\Request */
		private $request;

		/** @var \WPEmerge\Events\IncomingRequest */
		private $request_event;

		/** @var \WPEmerge\Responses\ResponseService */
		private $response_service;


		protected function setUp() : void {

			parent::setUp();


			$this->request_event = new IncomingRequest();
			$this->request_event->request = &$this->request;

			$this->response_service = new TestResponseService();

			$this->bootstrapTestApp();

			$this->disableMiddleware();

			$this->kernel = TestApp::resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );

		}

		protected function tearDown() : void {

			m::close();
			parent::tearDown();

			TestApp::setApplication( null );

		}

		/** @test */
		public function a_class_handler_is_auto_resolved_from_the_service_container() {

			TestApp::route()->get('/')
			       ->handle( DependencyController::class . '@handle' );


			$this->request = TestRequest::from('get', '/');
			$this->request->setType(IncomingWebRequest::class);


			$this->kernel->handle($this->request_event);

			$this->assertResponseSend();

			$this->assertSame( 'foo_web_controller', $this->responseBody() );

		}

		/** @test */
		public function a_class_handler_gets_route_arguments_passed_to_the_method() {

			TestApp::route()
			       ->get('teams/{team}')
			       ->handle( TeamsController::class . '@noTypeHint' );

			$this->request = TestRequest::from('get', 'teams/dortmund');
			$this->request->setType(IncomingWebRequest::class);

			$this->kernel->handle($this->request_event);

			$this->assertResponseSend();

			$this->assertSame( 'dortmund', $this->responseBody() );


		}

		/** @test */
		public function a_method_dependency_gets_resolved_from_the_service_container() {

			TestApp::route()
			       ->get('/')
			       ->handle( DependencyController::class . '@withMethodDependency' );

			$this->request = TestRequest::from('get', '/');
			$this->request->setType(IncomingWebRequest::class);


			$this->kernel->handle($this->request_event);

			$this->assertResponseSend();

			$this->assertSame( 'foobar', $this->responseBody() );

		}

		/** @test */
		public function web_controllers_can_be_resolved_without_the_full_namespace() {

			TestApp::route()
			       ->get('web')
			       ->handle( 'WebController@handle' );

			$this->request = TestRequest::from('get', 'web');
			$this->request->setType(IncomingWebRequest::class);

			$this->kernel->handle($this->request_event);

			$this->assertResponseSend();

			$this->assertSame( 'web_controller', $this->responseBody() );


		}

		/** @test */
		public function admin_controllers_can_be_resolved_without_the_full_namespace() {

			TestApp::route()
			       ->get('admin')
			       ->handle( 'AdminController@handle' );

			$this->request = TestRequest::from('get', 'admin');
			$this->request->setType(IncomingWebRequest::class);

			$this->kernel->handle($this->request_event);

			$this->assertResponseSend();

			$this->assertSame( 'admin_controller', $this->responseBody() );


		}

		/** @test */
		public function ajax_controllers_can_be_resolved_without_the_full_namespace() {

			TestApp::route()
			       ->get('foo')
			       ->handle( 'AjaxController@handle' );


			$this->request = TestRequest::from('get', 'foo');
			$this->request->setType(IncomingWebRequest::class);

			$this->kernel->handle($this->request_event);

			$this->assertResponseSend();

			$this->assertSame( 'ajax_controller', $this->responseBody() );

		}

		public function config() : array {

			return TEST_CONFIG;

		}

	}


