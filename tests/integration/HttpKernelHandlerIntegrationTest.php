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
	class HttpKernelHandlerIntegrationTest extends WPTestCase {


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


			$test_response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'foobar', $test_response->body() );

		}

		/** @test */
		public function a_class_handler_gets_route_arguments_passed_to_the_method() {

			TestApp::route()
			       ->get()
			       ->url( '/teams/{team}' )
			       ->handle( ClassHandlerConstructorDependency::class . '@teams' );


			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/teams/dortmund' );


			$test_response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'dortmund', $test_response->body()  );


		}

		/** @test */
		public function a_method_dependency_gets_resolved_from_the_service_container() {

			TestApp::route()
			       ->get()
			       ->url( '/' )
			       ->handle( ClassHandlerConstructorDependency::class . '@teamDependency' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/' );


			$test_response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'dortmund', $test_response->body() );

		}

		/** @test */
		public function web_controllers_can_be_resolved_without_the_full_namespace () {

			TestApp::route()
			       ->get()
			       ->url( '/web' )
			       ->handle( 'WebController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/web' );


			$test_response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'web_controller', $test_response->body() );


		}

		/** @test */
		public function admin_controllers_can_be_resolved_without_the_full_namespace () {

			TestApp::route()
			       ->get()
			       ->url( '/admin' )
			       ->handle( 'AdminController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/admin' );


			$test_response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'admin_controller', $test_response->body() );


		}

		/** @test */
		public function ajax_controllers_can_be_resolved_without_the_full_namespace() {

			TestApp::route()
			       ->get()
			       ->url( '/ajax' )
			       ->handle( 'AjaxController@handle' );

			$this->request->shouldReceive( 'getUrl' )
			              ->andReturn( 'https://wpemerge.test/ajax' );

			$test_response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'ajax_controller', $test_response->body() );

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

		private function config() : array {

			return [

				'controller_namespaces' => [

					'web'   => 'Tests\stubs\Controllers\Web',
					'admin' => 'Tests\stubs\Controllers\Admin',
					'ajax'  => 'Tests\stubs\Controllers\Ajax',

				],

			];


		}

	}


