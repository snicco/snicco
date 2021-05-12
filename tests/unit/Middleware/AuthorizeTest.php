<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Middleware;

	use Tests\TestCase;
	use Tests\TestRequest;
	use WPEmerge\Facade\WP;
	use WPEmerge\Middleware\Authorize;
	use WPEmerge\Exceptions\AuthorizationException;

	class AuthorizeTest extends TestCase {


		/**
		 * @var \WPEmerge\Middleware\Authorize
		 */
		private $middleware;

		/**
		 * @var \Closure
		 */
		private $route_action;

		/**
		 * @var \Tests\TestRequest
		 */
		private $request;

		protected function afterSetUp() : void {

			$this->middleware   = new Authorize();
			$this->route_action = function () {

				return 'foo';
			};
			$this->request      = TestRequest::from( 'GET', '/foo' );
		}



		/** @test */
		public function a_user_with_given_capabilities_can_access_the_route() {

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'manage_options' )
			  ->andReturnTrue();

			$response = $this->middleware->handle(
				$this->request,
				$this->route_action,
				'manage_options'
			);

			$this->assertSame( 'foo', $response );

		}

		/** @test */
		public function a_user_without_authorisation_to_the_route_will_throw_an_exception() {

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'manage_options' )
			  ->andReturnFalse();

			$this->expectException( AuthorizationException::class );

			$this->middleware->handle( $this->request, $this->route_action, 'manage_options' );


		}

		/** @test */
		public function the_user_can_be_authorized_against_a_resource() {

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'edit_post', 10 )
			  ->once()
			  ->andReturnTrue();

			$response = $this->middleware->handle(
				$this->request,
				$this->route_action,
				'edit_post',
				10
			);

			$this->assertSame( 'foo', $response );

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'edit_post', 10 )
			  ->once()
			  ->andReturnFalse();

			$this->expectException( AuthorizationException::class );

			$this->middleware->handle(
				$this->request,
				$this->route_action,
				'edit_post',
				10
			);

		}

		/** @test */
		public function several_wordpress_specific_arguments_can_be_passed() {

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'edit_post_meta', 10, 'test_key' )
			  ->once()
			  ->andReturnTrue();

			$response = $this->middleware->handle(
				$this->request,
				$this->route_action,
				'edit_post_meta',
				10,
				'test_key'
			);

			$this->assertSame( 'foo', $response );

			WP::shouldReceive( 'currentUserCan' )
			  ->with( 'edit_post_meta', 10, 'test_key' )
			  ->once()
			  ->andReturnFalse();

			$this->expectException( AuthorizationException::class );

			$this->middleware->handle(
				$this->request,
				$this->route_action,
				'edit_post_meta',
				10,
				'test_key'
			);

		}


	}
