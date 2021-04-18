<?php


	namespace Tests\integration;

	use Codeception\TestCase\WPTestCase;
	use GuzzleHttp\Psr7\Response as Psr7Response;
	use GuzzleHttp\Psr7\Utils;
	use Mockery as m;
	use WPEmerge\Requests\Request;
	use WPEmerge\Responses\ResponseService;
	use Tests\stubs\Handlers\ClassHandlerConstructorDependency;
	use Tests\stubs\IntegrationTestErrorHandler;
	use Tests\stubs\TestApp;

	/**
	 * @covers \WPEmerge\Kernels\HttpKernel
	 */
	class HttpKernelIntegrationTest extends WPTestCase {


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
			$this->request->shouldReceive( 'withAttribute' )->andReturn( $this->request );
			$this->response_service = m::mock( ResponseService::class );

			$this->bootstrapTestApp();


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
			       ->handle( ClassHandlerConstructorDependency::class . '@handle' );

			$this->request->bar = 'bar';

			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/' );

			$handler_response = 'foobarindex';

			$this->response_service->shouldReceive( 'output' )
			                       ->with( $handler_response )
			                       ->andReturn( $this->response( $handler_response ) );

			$response = $this->kernel->handle( $this->request, [ 'index' ] );

			$this->assertSame( 'foobarindex', $this->responseBody( $response ) );

		}

		/** @test */
		public function a_class_handler_gets_route_arguments_passed_to_the_method() {

			TestApp::route()
			       ->get()
			       ->url( '/teams/{team}' )
			       ->handle( ClassHandlerConstructorDependency::class . '@teams' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/teams/dortmund' );

			$this->response_service->shouldReceive( 'output' )
			                       ->with( 'dortmund' )
			                       ->andReturn( $this->response( 'dortmund' ) );

			$response = $this->kernel->handle( $this->request, [ 'index' ] );

			$this->assertSame( 'dortmund', $this->responseBody( $response ) );


		}

		/** @test */
		public function a_method_dependency_gets_resolved_from_the_service_container() {

			TestApp::route()
			       ->get()
			       ->url( '/' )
			       ->handle( ClassHandlerConstructorDependency::class . '@teamDependency' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/' );

			$this->response_service->shouldReceive( 'output' )
			                       ->with( 'dortmund' )
			                       ->andReturn( $this->response( 'dortmund' ) );

			$response = $this->kernel->handle( $this->request, [ 'index' ] );

			$this->assertSame( 'dortmund', $this->responseBody( $response ) );

		}

		/** @test */
		public function web_controllers_can_be_resolved_without_the_full_namespace () {

			TestApp::route()
			       ->get()
			       ->url( '/web' )
			       ->handle( 'WebController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/web' );

			$this->response_service->shouldReceive( 'output' )
			                       ->with( 'web_controller' )
			                       ->andReturn( $this->response( 'web_controller' ) );

			$response = $this->kernel->handle( $this->request, [ 'index' ] );

			$this->assertSame( 'web_controller', $this->responseBody( $response ) );


		}

		/** @test */
		public function admin_controllers_can_be_resolved_without_the_full_namespace () {

			TestApp::route()
			       ->get()
			       ->url( '/admin' )
			       ->handle( 'AdminController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/admin' );

			$this->response_service->shouldReceive( 'output' )
			                       ->with( 'admin_controller' )
			                       ->andReturn( $this->response( 'admin_controller' ) );

			$response = $this->kernel->handle( $this->request, [ 'index' ] );

			$this->assertSame( 'admin_controller', $this->responseBody( $response ) );


		}

		/** @test */
		public function ajax_controllers_can_be_resolved_without_the_full_namespace(  ) {

			TestApp::route()
			       ->get()
			       ->url( '/ajax' )
			       ->handle( 'AjaxController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/ajax' );

			$this->response_service->shouldReceive( 'output' )
			                       ->with( 'ajax_controller' )
			                       ->andReturn( $this->response( 'ajax_controller' ) );

			$response = $this->kernel->handle( $this->request, [ 'index' ] );

			$this->assertSame( 'ajax_controller', $this->responseBody( $response ) );

		}









		private function bootstrapTestApp() {

			TestApp::make()->bootstrap( $this->config() , false );
			TestApp::container()[ WPEMERGE_REQUEST_KEY ]                  = $this->request;
			TestApp::container()[ WPEMERGE_RESPONSE_SERVICE_KEY ]         = $this->response_service;
			TestApp::container()[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ] = new IntegrationTestErrorHandler();

		}

		private function response( string $handler_response ) : Psr7Response {

			$response = new Psr7Response();

			return $response->withBody( Utils::streamFor( $handler_response ) );
		}

		private function responseBody( Psr7Response $response ) {

			return $response->getBody()->read( 30 );

		}

		private function config() {

			return [

				'controller_namespaces' => [

					'web'   => 'Tests\stubs\Controllers\Web',
					'admin' => 'Tests\stubs\Controllers\Admin',
					'ajax'  => 'Tests\stubs\Controllers\Ajax',

				],

			];


		}

	}


