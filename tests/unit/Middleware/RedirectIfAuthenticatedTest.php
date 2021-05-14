<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Middleware;

	use Tests\Test;
	use Tests\TestRequest;
	use WPEmerge\Facade\WP;
	use WPEmerge\Middleware\RedirectIfAuthenticated;
	use WPEmerge\Http\RedirectResponse;

	/** @todo Fix tests */
	class RedirectIfAuthenticatedTest extends Test {


		/**
		 * @var \Closure
		 */
		private $route_action;

		/**
		 * @var RedirectIfAuthenticated
		 */
		private $middleware;

		/**
		 * @var \Tests\TestRequest
		 */
		private $request;

		protected function afterSetUp() : void {


			$this->middleware   = new RedirectIfAuthenticated();
			$this->route_action = function () {

				return 'foo';

			};
			$this->request      = TestRequest::from( 'GET', '/foo' );

			WP::shouldReceive('homeUrl')->andReturn('foobar.com')->byDefault();

		}

		// /** @test */
		public function guest_can_access_the_route() {

			WP::shouldReceive('isUserLoggedIn')->andReturnFalse();

			$response = $this->middleware->handle( $this->request, $this->route_action );

			$this->assertSame( 'foo', $response );

		}

		// /** @test */
		public function logged_in_users_are_redirected_to_the_home_url() {

			WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
			WP::shouldReceive('homeUrl')
			  ->with('', 'https')
			  ->andReturn(SITE_URL);

			$response = $this->middleware->handle( $this->request, $this->route_action );

			$this->assertInstanceOf( RedirectResponse::class, $response );

			$this->assertSame( SITE_URL, $response->header( 'Location' ) );

		}

		// /** @test */
		public function logged_in_users_can_be_redirected_to_custom_urls() {

			WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
			WP::shouldReceive('homeUrl')
			  ->with('', 'https')
			  ->andReturn('https://example.com');

			$response = $this->middleware->handle( $this->request, $this->route_action, 'https://example.com' );

			$this->assertInstanceOf( RedirectResponse::class, $response );

			$this->assertSame( 'https://example.com/', $response->header( 'Location' ) );
		}

	}
