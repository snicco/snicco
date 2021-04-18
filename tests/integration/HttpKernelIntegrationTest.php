<?php


	namespace WPEmergeTests\integration;

	use Codeception\TestCase\WPTestCase;
	use GuzzleHttp\Psr7\Response as Psr7Response;
	use Mockery as m;
	use WPEmerge\Requests\Request;
	use WPEmerge\Responses\ResponseService;
	use WPEmergeTestTools\Handlers\ClassHandlerConstructorDependency;
	use WPEmergeTestTools\IntegrationTestErrorHandler;
	use WPEmergeTestTools\TestApp;

	/**
	 * @covers \WPEmerge\Kernels\HttpKernel
	 */
	class HttpKernelIntegrationTest extends WPTestCase {


		/**
		 * @var \WPEmerge\Kernels\HttpKernel
		 */
		private $kernel;

		/** @var \WPEmerge\Requests\Request  */
		private $request;

		/** @var \WPEmerge\Responses\ResponseService  */
		private $response_service;

		protected function setUp() : void {

			parent::setUp();

			$this->request = m::mock(Request::class);
			$this->request->shouldReceive('getMethod')->andReturn('GET');
			$this->request->shouldReceive('withAttribute')->andReturn($this->request);
			$this->response_service =  m::mock(ResponseService::class);

			$this->bootstrapTestApp();

			$this->kernel = TestApp::resolve(WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY);

		}

		protected function tearDown() : void {

			m::close();
			parent::tearDown();
		}

		/** @test */
		public function a_class_handler_is_auto_resolved_from_the_service_container () {

			TestApp::route()->get()->url('/')->handle( ClassHandlerConstructorDependency::class . '@handle');

			$this->request->bar = 'bar';

			$this->request->shouldReceive('getUrl')->andReturn('https://wpemerge.test/');

			$this->response_service->shouldReceive('output')
			                       ->with('foobarindex')
			                       ->andReturn( $expected_response = new Psr7Response() );

			$response = $this->kernel->handle($this->request, ['index']);

			$this->assertSame( $expected_response , $response);

		}

		/** @test */
		public function a_class_handler_gets_route_arguments_passed_to_the_method () {

			TestApp::route()
			       ->get()
			       ->url('/teams/{team}')
			       ->handle( ClassHandlerConstructorDependency::class . '@teams');


			$this->request->shouldReceive('getUrl')
			              ->andReturn('https://wpemerge.test/teams/dortmund');

			$this->response_service->shouldReceive('output')
			                       ->with('dortmund')
			                       ->andReturn( $expected_response = new Psr7Response() );

			$response = $this->kernel->handle($this->request, ['index']);

			$this->assertSame( $expected_response , $response);


		}

		/** @test */
		public function a_method_dependency_gets_resolved_from_the_service_container(  ) {

			TestApp::route()
			       ->get()
			       ->url('/')
			       ->handle( ClassHandlerConstructorDependency::class . '@teamDependency');


			$this->request->shouldReceive('getUrl')
			              ->andReturn('https://wpemerge.test/');

			$this->response_service->shouldReceive('output')
			                       ->with('dortmund')
			                       ->andReturn( $expected_response = new Psr7Response() );

			$response = $this->kernel->handle($this->request, ['index']);

			$this->assertSame( $expected_response , $response);

		}

		private function bootstrapTestApp () {

			TestApp::make()->bootstrap( [], false );
			TestApp::container()[WPEMERGE_REQUEST_KEY] = $this->request;
			TestApp::container()[WPEMERGE_RESPONSE_SERVICE_KEY] = $this->response_service;
			TestApp::container()[WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY] = new IntegrationTestErrorHandler();

		}


	}


