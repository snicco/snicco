<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Middleware;

	use Codeception\TestCase\WPTestCase;
	use Tests\TestRequest;
	use WPEmerge\Middleware\RedirectIfAuthenticated;
	use WPEmerge\Http\RedirectResponse;

	class RedirectIfAuthenticatedTest extends WPTestCase {

		use WordpressFixtures;

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

		protected function setUp() : void {

			parent::setUp();

			$this->middleware   = new RedirectIfAuthenticated();
			$this->route_action = function () {

				return 'foo';

			};
			$this->request      = TestRequest::from( 'GET', '/foo' );

		}

		/** @test */
		public function guest_can_access_the_route() {

			$calvin = $this->newAdmin();
			$this->logout($calvin);

			$response = $this->middleware->handle($this->request, $this->route_action);

			$this->assertSame('foo', $response);

		}

		/** @test */
		public function logged_in_users_are_redirected_to_the_home_url () {

			$calvin = $this->newAdmin();
			$this->login($calvin);

			$response = $this->middleware->handle($this->request, $this->route_action);

			$this->assertInstanceOf(RedirectResponse::class , $response);

			$this->assertSame(SITE_URL, $response->header('Location'));

		}

		/** @test */
		public function logged_in_users_can_be_redirected_to_custom_urls () {


			$calvin = $this->newAdmin();
			$this->login($calvin);

			$response = $this->middleware->handle($this->request, $this->route_action, 'https://example.com');

			$this->assertInstanceOf(RedirectResponse::class , $response);

			$this->assertSame('https://example.com/', $response->header('Location'));
		}

	}
