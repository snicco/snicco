<?php


	namespace Tests\integration\HttpKernel;

	use BetterWpHooks\Contracts\Dispatcher;
	use Codeception\TestCase\WPTestCase;
	use Tests\integration\SetUpTestApp;
	use Tests\MockRequest;
	use Tests\stubs\TestApp;
	use Tests\stubs\TestResponseService;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Requests\Request;
	use Mockery as m;

	class HttpKernelWpHookInteractionsTest extends WPTestCase {


		use SetUpTestApp;
		use MockRequest;

		/**
		 * @var \WPEmerge\HttpKernel
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


		}

		protected function tearDown() : void {

			m::close();
			parent::tearDown();

			TestApp::setApplication( null );


		}


		/** @test */
		public function the_body_will_never_be_sent_when_the_kernel_did_not_receive_a_response() {

			/** @var \BetterWpHooks\Dispatchers\WordpressDispatcher $dispatcher */
			$dispatcher = TestApp::resolve( Dispatcher::class );

			$dispatcher->dispatch( new AdminBodySendable() );

			$this->assertNull($this->response_service->body_response);


		}

		public function config() : array {

			return TEST_CONFIG;

		}

	}