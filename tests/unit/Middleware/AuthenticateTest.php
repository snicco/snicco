<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Middleware;

	use Closure;
    use Tests\Test;
	use Tests\TestRequest;
	use WPEmerge\Facade\WP;
	use WPEmerge\Middleware\Authenticate;
	use WPEmerge\Http\RedirectResponse;

	/** @todo Fix tests  */
	class AuthenticateTest extends Test {


		/**
		 * @var Authenticate
		 */
		private $middleware;

		/**
		 * @var Closure
		 */
		private $route_action;

		/**
		 * @var TestRequest
		 */
		private $request;

		protected function setUp() : void {

			parent::setUp();

			$this->middleware   = new Authenticate();
			$this->route_action = function () {

				return 'foo';

			};
			$this->request      = TestRequest::from( 'GET', '/foo' );

			WP::shouldReceive('loginUrl')->andReturn('foobar.com')->byDefault();

		}

		// /** @test */
		public function logged_in_users_can_access_the_route() {

			WP::shouldReceive('isUserLoggedIn')->andReturnTrue();

			$response = $this->middleware->handle( $this->request, $this->route_action );

			$this->assertSame( 'foo', $response );


		}


		// /** @test */
		public function logged_out_users_cant_access_the_route() {

			WP::shouldReceive('isUserLoggedIn')->andReturnFalse();

			$response = $this->middleware->handle( $this->request, $this->route_action );

			$this->assertInstanceOf( RedirectResponse::class, $response );

		}


		// /** @test */
		public function by_default_users_get_redirected_to_wp_login_with_the_current_url_added_to_the_query_args() {

			WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
			WP::shouldReceive('loginUrl')->andReturnUsing(function ($redirect_to) {

				return 'example.com/login?redirect=' . $redirect_to;

			});

			$expected = 'example.com/login?redirect=' . $this->request->url();

			$response = $this->middleware->handle( $this->request, $this->route_action );

			$this->assertSame( $expected, $response->header( 'Location' ) );


		}


		// /** @test */
		public function users_can_be_redirected_to_a_custom_url() {

			WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
			WP::shouldReceive('loginUrl')->times(0);

			$expected = 'https://foobar.com';

			$response = $this->middleware->handle( $this->request, $this->route_action, 'https://foobar.com' );

			$this->assertSame( $expected, $response->header( 'Location' ) );

		}

	}
