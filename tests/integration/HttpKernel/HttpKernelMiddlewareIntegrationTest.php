<?php


	namespace Tests\integration\HttpKernel;

	use Codeception\TestCase\WPTestCase;
	use Tests\TestRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use Tests\stubs\TestApp;



	/**
	 * @covers \WPEmerge\Http\HttpKernel
	 */
	class HttpKernelMiddlewareIntegrationTest extends WPTestCase {


		/** @test */
		public function controller_middleware_gets_resolved_from_the_service_container () {

			TestApp::route()
			       ->get('wp-admin/dashboard')
			       ->handle( 'AdminControllerWithMiddleware@handle');

			$this->request = TestRequest::from('GET', 'wp-admin/dashboard');
			$this->request->setType(IncomingWebRequest::class);

			$this->kernel->handle($this->request_event);

			$this->assertSame( 'foo:foo_admin_controller_dependency', $this->responseBody() );


		}

		/** @test */
		public function when_a_controller_uses_controller_middleware_it_only_ever_gets_resolved_once() {

			$GLOBALS['controller_constructor_count'] = 0;

			TestApp::route()
			       ->get('wp-admin/dashboard')
			       ->handle( 'AdminControllerWithMiddleware@handle');

			$this->request = TestRequest::from('GET', 'wp-admin/dashboard');
			$this->request->setType(IncomingWebRequest::class);


			$this->kernel->handle($this->request_event);

			$this->assertSame( 'foo:foo_admin_controller_dependency', $this->responseBody() );
			$this->assertSame( 1, $GLOBALS['controller_constructor_count']);

			unset($GLOBALS['controller_constructor_count']);


		}

		/** @test */
		public function global_middleware_gets_resolved_from_the_service_container() {


			$this->assertSame(0 ,($GLOBALS['global_middleware_executed_times']));

			TestApp::route()
			       ->get('/')
			       ->middleware(FooooooMiddleware::class)
			       ->handle( 'WebController@request');


			$this->request = TestRequest::from('GET', '/');
			$this->request->setType(IncomingWebRequest::class);


			$this->kernel->handle($this->request_event);

			$this->assertSame( 'foo:foo_web_controller', $this->responseBody() );

			$this->assertSame(1, $GLOBALS['global_middleware_executed_times']);


		}




	}


