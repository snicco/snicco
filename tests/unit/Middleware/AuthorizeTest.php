<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Middleware;

	use Codeception\TestCase\WPTestCase;
	use Tests\TestRequest;
	use WPEmerge\Middleware\Authorize;
	use WPEmerge\Exceptions\AuthorizationException;

	class AuthorizeTest extends WPTestCase {

		use WordpressFixtures;

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

		protected function setUp() : void {

			parent::setUp();

			$this->middleware = new Authorize();
			$this->route_action = function () {

				return 'foo';
			};
			$this->request = TestRequest::from('GET', '/foo');
		}

		/** @test */
		public function a_user_with_given_capabilities_can_access_the_route () {


			$this->login( $this->newAdmin() );

			$response = $this->middleware->handle(
				$this->request,
				$this->route_action,
				'manage_options'
			);

			$this->assertSame( 'foo', $response );

		}


		/** @test */
		public function a_user_without_authorisation_to_the_route_will_throw_an_exception () {


			$this->login( $this->newAuthor() );

			$this->expectException(AuthorizationException::class);

			$this->middleware->handle($this->request, $this->route_action, 'manage_options');


		}


		/** @test */
		public function the_user_can_be_authorized_against_a_resource () {


			$post = $this->newPost( $calvin = $this->newAuthor() );

			$this->login($calvin);

			$response = $this->middleware->handle(
				$this->request,
				$this->route_action,
				'edit_post',
				$post->ID
			);

			$this->assertSame( 'foo', $response );

			$this->login( $john = $this->newAuthor());

			$this->expectException(AuthorizationException::class);

			$this->middleware->handle(
				$this->request,
				$this->route_action,
				'edit_post',
				$post->ID
			);

		}


		/** @test */
		public function several_wordpress_specific_arguments_can_be_passed () {

			$post = $this->newPost( $calvin = $this->newAuthor() );
			update_post_meta($post->ID, 'test_key', 'foo');
			$this->login($calvin);

			$response = $this->middleware->handle(
				$this->request,
				$this->route_action,
				'edit_post_meta',
				$post->ID,
				'test_key'
			);

			$this->assertSame( 'foo', $response );

			$this->login( $john = $this->newAuthor());

			$this->expectException(AuthorizationException::class);

			$this->middleware->handle(
				$this->request,
				$this->route_action,
				'edit_post_meta',
				$post->ID,
				'test_key'
			);

		}



	}
