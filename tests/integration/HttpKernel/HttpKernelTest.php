<?php


	declare( strict_types = 1 );


	namespace Tests\integration\HttpKernel;

	use PHPUnit\Framework\TestCase;
	use Tests\TestRequest;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Events\HeadersSent;
	use WPEmerge\Http\Response;

	class HttpKernelTest extends TestCase {

		use SetUpKernel;

		/** @test */
		public function no_response_gets_send_when_no_route_matched() {

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );

			$output = $this->runAndGetKernelOutput($request);

			$this->assertNothingSent($output);

		}

		/** @test */
		public function for_matching_request_headers_and_body_get_send() {

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return new Response( 'foo' );

			} );

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );

			$this->assertBodySent('foo', $this->runAndGetKernelOutput($request));

		}

		/** @test */
		public function for_admin_requests_the_body_does_not_get_send_immediately () {

			$this->router->get( '/admin', function () {

				return new Response( 'foo' );

			} );

			$request = $this->createIncomingAdminRequest( 'GET', '/admin' );

			$this->assertNothingSent($this->runAndGetKernelOutput($request));

			ob_start();
			$this->kernel->sendBodyDeferred();
			$body = ob_get_clean();

			$this->assertBodySent('foo', $body);

		}

		/** @test */
		public function events_are_dispatched_when_a_headers_and_body_get_send () {

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return new Response( $request );

			} );

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );
			$this->runAndGetKernelOutput($request);

			ApplicationEvent::assertDispatched(HeadersSent::class , function ($event) use ( $request ) {

				return $event->request = $request;


			});
			ApplicationEvent::assertDispatched(BodySent::class , function ($event) use ( $request ) {

				return $event->request = $request;


			});


		}

		/** @test */
		public function the_body_will_never_be_sent_when_the_kernel_did_not_receive_a_response_for_admin_requests() {

			ob_start();
			$this->kernel->sendBodyDeferred();

			$this->assertNothingSent(ob_get_clean());

		}




	}