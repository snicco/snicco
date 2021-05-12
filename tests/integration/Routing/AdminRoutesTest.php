<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use Mockery;
	use PHPUnit\Framework\TestCase;
	use Tests\TestRequest;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Facade\WP;
	use WPEmerge\Support\Url;
	use WpFacade\WpFacade;

	class AdminRoutesTest extends TestCase {

		use SetUpRouter;

		protected function tearDown() : void {

			WpFacade::clearResolvedInstances();
			Mockery::close();

			parent::tearDown();

		}


		/** @test */
		public function routes_in_an_admin_group_match_without_needing_to_specify_the_full_path() {

			WP::shouldReceive('isAdmin')->andReturnTrue();

			$this->router->group( [ 'prefix' => 'wp-admin/admin.php' ] , function () {

				$this->router->get( 'foo', function ( RequestInterface $request, string $page ) {

					return $page;
				} );

			} );

			$request = TestRequest::fromFullUrl( 'GET', $this->adminUrlTo( 'foo' ) );

			$this->assertSame( 'foo', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function routes_to_different_admin_pages_dont_match() {


			$this->router->group( [ 'prefix' => 'wp-admin/admin.php' ], function () {

				$this->router->get( 'foo', function () {

					return 'foo';
				} );

			} );

			$request = TestRequest::fromFullUrl( 'GET', $this->adminUrlTo( 'bar' ) );

			$this->assertSame( null, $this->router->runRoute( $request ) );


		}

		/** @test */
		public function the_admin_preset_works_with_nested_route_groups() {

			WP::shouldReceive('isAdmin')->andReturnTrue();

			$this->router->group( [ 'prefix' => 'wp-admin/admin.php' ], function () {

				$this->router->group( [ 'name' => 'foo_group' ], function () {

					$this->router->get( 'foo', function ( RequestInterface $request, string $page ) {

						return $page;
					} );

				} );


			} );

			$request = TestRequest::fromFullUrl( 'GET', $this->adminUrlTo( 'foo' ) );

			$this->assertSame( 'foo', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function two_different_admin_routes_can_be_created() {

			WP::shouldReceive('isAdmin')->andReturnTrue();

			$routes = function () {

				$this->router->group( [ 'prefix' => 'wp-admin/admin.php' ] , function () {

					$this->router->get( 'foo', function ( RequestInterface $request, string $page ) {

						return $page;

					} );

					$this->router->get( 'bar', function ( RequestInterface $request, string $page ) {

						return $page;

					} );

				} );

			};

			$this->newRouterWith( $routes );
			$request = TestRequest::fromFullUrl( 'GET', $this->adminUrlTo( 'foo' ) );
			$this->assertSame( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = TestRequest::fromFullUrl( 'GET', $this->adminUrlTo( 'bar' ) );
			$this->assertSame( 'bar', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = TestRequest::fromFullUrl( 'POST', $this->adminUrlTo( 'bar' ) );
			$this->assertSame( null, $this->router->runRoute( $request ) );


		}

		public function adminUrlTo(string $menu_slug ) {

			$url = Url::combinePath(SITE_URL, 'wp-admin/admin.php?page=' . $menu_slug);

			return $url;

		}


	}