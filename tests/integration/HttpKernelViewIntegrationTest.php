<?php
//
//
// 	namespace Tests\integration;
//
// 	use Codeception\TestCase\WPTestCase;
// 	use Tests\stubs\IntegrationTestErrorHandler;
// 	use Tests\stubs\Middleware\FooMiddleware;
// 	use Tests\stubs\TestApp;
// 	use WPEmerge\Middleware\SubstituteBindings;
// 	use WPEmerge\Requests\Request;
// 	use WPEmerge\Responses\ResponseService;
// 	use Mockery as m;
//
// 	class HttpKernelViewIntegrationTest extends WPTestCase {
//
// 		use DisableMiddleware;
//
// 		private $view_dir;
//
// 		/**
// 		 * @var \WPEmerge\Kernels\HttpKernel
// 		 */
// 		private $kernel;
//
// 		/** @var \WPEmerge\Requests\Request */
// 		private $request;
//
// 		/** @var \WPEmerge\Responses\ResponseService */
// 		private $response_service;
//
// 		protected function setUp() : void {
//
// 			parent::setUp();
//
// 			$this->view_dir = getenv( 'PACKAGE_ROOT' ) .
// 			                  DIRECTORY_SEPARATOR . 'tests' .
// 			                  DIRECTORY_SEPARATOR . 'views' .
// 			                  DIRECTORY_SEPARATOR;
//
// 			$this->request = m::mock( Request::class );
// 			$this->request->shouldReceive( 'getMethod' )->andReturn( 'GET' );
// 			$this->request->shouldReceive( 'withAttribute' )->andReturn( $this->request );
// 			$this->response_service = m::mock( ResponseService::class );
//
// 			$this->bootstrapTestApp();
//
// 			$this->disableGlobalMiddleware();
//
// 			$this->kernel = TestApp::resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );
//
// 		}
//
// 		protected function tearDown() : void {
//
// 			m::close();
// 			parent::tearDown();
//
// 			TestApp::setApplication( null );
//
// 		}
//
// 		/** @test */
// 		public function the_default_wordpress_view_path_is_returned_if_a_controller_returns_null() {
//
//
// 			TestApp::route()
// 			       ->get()
// 			       ->url( '/' )
// 			       ->handle( 'WebController@nullResponse' );
//
// 			$this->request->shouldReceive( 'getUrl' )
// 			              ->andReturn( 'https://wpemerge.test' );
//
// 			$response = $this->kernel->handle(
// 				$this->request, $this->view_dir . 'welcome.wordpress.php'
// 			);
//
// 			$this->assertSame( $this->view_dir . 'welcome.wordpress.php', $response );
//
// 		}
//
// 		/** @test */
// 		public function web_controllers_dont_receive_the_view_variable() {
//
// 			TestApp::route()
// 			       ->get()
// 			       ->url( '/{no_view}' )
// 			       ->handle( 'WebController@assertNoView' );
//
// 			$this->request->shouldReceive( 'getUrl' )
// 			              ->andReturn( 'https://wpemerge.test/no_view' );
//
// 			$response = $this->kernel->handle(
// 				$this->request, $this->view_dir . 'welcome.wordpress.php'
// 			);
//
// 			$this->assertSame( 'no_view', $response->body() );
//
//
// 		}
//
// 		/** @test */
// 		public function admin_controllers_dont_receive_the_view_variable() {
//
// 			TestApp::route()
// 			       ->get()
// 			       ->url( '/{no_view}' )
// 			       ->handle( 'AdminController@assertNoView' );
//
// 			$this->request->shouldReceive( 'getUrl' )
// 			              ->andReturn( 'https://wpemerge.test/no_view' );
//
// 			$response = $this->kernel->handle(
// 				$this->request, $this->view_dir . 'welcome.wordpress.php'
// 			);
//
// 			$this->assertSame( 'no_view', $response->body() );
//
// 		}
//
// 		/** @test */
// 		public function ajax_controllers_dont_receive_the_view_variable() {
//
// 			TestApp::route()
// 			       ->get()
// 			       ->url( '/{no_view}' )
// 			       ->handle( 'AjaxController@assertNoView' );
//
// 			$this->request->shouldReceive( 'getUrl' )
// 			              ->andReturn( 'https://wpemerge.test/no_view' );
//
// 			$response = $this->kernel->handle(
// 				$this->request, $this->view_dir . 'welcome.wordpress.php'
// 			);
//
// 			$this->assertSame( 'no_view', $response->body() );
//
//
// 		}
//
//
//
// 		private function bootstrapTestApp() {
//
// 			TestApp::make()->bootstrap( $this->config(), false );
// 			TestApp::container()[ WPEMERGE_REQUEST_KEY ]                  = $this->request;
// 			TestApp::container()[ WPEMERGE_RESPONSE_SERVICE_KEY ]         = $this->response_service;
// 			TestApp::container()[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ] = new IntegrationTestErrorHandler();
//
// 		}
//
// 		private function config() : array {
//
// 			return array_merge(TEST_CONFIG, [
//
//
// 				'middleware' => [
//
// 					'foo' => FooMiddleware::class,
//
// 				],
//
// 				'views' => [ getenv( 'PACKAGE_ROOT' ) . DS . 'tests' . DS . 'views' ],
//
// 			]);
//
//
// 		}
//
//
// 	}