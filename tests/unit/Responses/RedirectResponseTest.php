<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Responses;

	use PHPUnit\Framework\TestCase;
	use Tests\AssertsResponse;
	use Tests\TestRequest;
	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Http\RedirectResponse;

	class RedirectResponseTest extends TestCase {

		use AssertsResponse;

		/** @test */
		public function a_working_redirection_can_be_created_via_the_constructor() {

			$request = $this->createRequest();

			$response = new RedirectResponse( $request, 302, 'https://foobar.com/' );

			$this->assertInstanceOf( ResponseInterface::class, $response );
			$this->assertStatusCode( 302, $response );
			$this->assertOutput( '', $response );
			$this->assertHeader( 'Location', 'https://foobar.com/', $response );


		}

		/** @test */
		public function a_redirect_can_be_created_by_calling_a_method_after_construction() {

			$request = $this->createRequest();

			$response = ( new RedirectResponse( $request, 302 ) )->to( 'https://foobar.com/' );

			$this->assertInstanceOf( ResponseInterface::class, $response );
			$this->assertStatusCode( 302, $response );
			$this->assertOutput( '', $response );
			$this->assertHeader( 'Location', 'https://foobar.com/', $response );

		}

		/** @test */
		public function the_status_code_can_be_adjusted() {

			$request = $this->createRequest();

			$response = new RedirectResponse( $request, 301 );

			$this->assertStatusCode( 301, $response );


		}

		/** @test */
		public function a_redirect_to_the_previous_request_location_can_be_created() {

			$expected = 'https://example.com/?hello=world';

			$request = $this->createRequest()->setHeader( 'Referer', $expected );

			$response = ( new RedirectResponse( $request ) )->back();

			$this->assertInstanceOf( ResponseInterface::class, $response );
			$this->assertStatusCode( 302, $response );
			$this->assertOutput( '', $response );
			$this->assertHeader( 'Location', $expected, $response );


		}

		/** @test */
		public function the_status_code_can_be_adjusted_when_redirecting_back() {

			$expected = 'https://example.com/?hello=world';

			$request = $this->createRequest()->setHeader( 'Referer', $expected );

			$response = ( new RedirectResponse( $request, 301 ) )->back();

			$this->assertInstanceOf( ResponseInterface::class, $response );
			$this->assertStatusCode( 301, $response );
			$this->assertOutput( '', $response );
			$this->assertHeader( 'Location', $expected, $response );


		}

		/** @test */
		public function a_fallback_url_can_be_set_for_when_no_referer_header_is_present() {

			$expected = 'https://fallback.com/';

			$request = $this->createRequest()->setHeader( 'Referer', '' );

			$response = ( new RedirectResponse( $request ) )->back( $expected );

			$this->assertInstanceOf( ResponseInterface::class, $response );
			$this->assertStatusCode( 302, $response );
			$this->assertOutput( '', $response );
			$this->assertHeader( 'Location', $expected, $response );


		}

		/** @test */
		public function if_no_fallback_and_no_referer_header_are_available_the_redirect_is_created_to_the_current_request_url() {


			$request = TestRequest::fromFullUrl( 'GET', 'https://example.com/foo?bar=baz' )
			                      ->setHeader( 'Referer', '' );

			$response = ( new RedirectResponse( $request ) )->back();

			$this->assertInstanceOf( ResponseInterface::class, $response );
			$this->assertStatusCode( 302, $response );
			$this->assertOutput( '', $response );
			$this->assertHeader( 'Location', 'https://example.com/foo?bar=baz', $response );


		}


	}
