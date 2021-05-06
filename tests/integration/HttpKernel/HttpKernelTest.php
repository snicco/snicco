<?php


	namespace Tests\integration\HttpKernel;

	use PHPUnit\Framework\TestCase;
	use Psr\Http\Message\ResponseInterface;
	use Tests\stubs\TestErrorHandler;
	use Tests\stubs\TestResponse;
	use Tests\TestRequest;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Events\HeadersSent;

	class HttpKernelTest extends TestCase {

		use SetUpKernel;

		/** @test */
		public function no_response_gets_send_when_no_route_matched() {

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );

			$this->kernel->handle( $request );


			$this->assertHeadersNotSent();
			$this->assertBodyNotSent();

		}

		/** @test */
		public function for_matching_request_headers_and_body_get_send() {

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return new TestResponse( $request );

			} );

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );
			$this->kernel->handle( $request );


			$this->assertHeadersSent();
			$this->assertBodySent();

		}

		/** @test */
		public function for_admin_requests_the_body_does_get_not_send_immediately () {

			$this->router->get( '/admin', function ( TestRequest $request ) {

				return new TestResponse( $request );

			} );

			$request = $this->createIncomingAdminRequest( 'GET', '/admin' );
			$this->kernel->handle( $request );

			$this->assertHeadersSent();
			$this->assertBodyNotSent();

			$this->kernel->sendBodyDeferred();

			$this->assertBodySent();

		}

		/** @test */
		public function events_are_dispatched_when_a_headers_and_body_get_send () {

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return new TestResponse( $request );

			} );

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );
			$this->kernel->handle( $request );

			ApplicationEvent::assertDispatched(HeadersSent::class , function ($event) use ( $request ) {

				return $event->request = $request;


			});
			ApplicationEvent::assertDispatched(BodySent::class , function ($event) use ( $request ) {

				return $event->request = $request;


			});


		}

		/** @test */
		public function the_body_will_never_be_sent_when_the_kernel_did_not_receive_a_response_for_admin_requests() {

			$this->kernel->sendBodyDeferred();

			$this->assertBodyNotSent();


		}


		private function assertHeadersSent() {

			$this->assertInstanceOf(
				ResponseInterface::class,
				$this->response_service->header_response );

		}

		private function assertHeadersNotSent() {

			$this->assertNull( $this->response_service->header_response );

		}

		private function assertBodySent() {

			$this->assertInstanceOf(
				ResponseInterface::class,
				$this->response_service->body_response );

			$this->assertSame(
				$this->response_service->body_response,
				$this->response_service->header_response
			);

		}

		private function assertBodyNotSent() {

			$this->assertNull( $this->response_service->body_response );

		}


	}