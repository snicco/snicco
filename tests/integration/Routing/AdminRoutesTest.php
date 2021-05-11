<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use PHPUnit\Framework\TestCase;
	use Tests\TestRequest;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Support\Url;

	class AdminRoutesTest extends TestCase {

		use SetUpRouter;

		protected function setUp() : void {

			parent::setUp();

			$this->newRouter();

			$this->route_collection->isAdmin();

			$GLOBALS['test'] = [];

		}


		/** @test */
		public function routes_in_an_admin_group_match_without_needing_to_specify_the_full_path() {

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
			$this->route_collection->isAdmin();
			$request = TestRequest::fromFullUrl( 'GET', $this->adminUrlTo( 'foo' ) );
			$this->assertSame( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$this->route_collection->isAdmin();
			$request = TestRequest::fromFullUrl( 'GET', $this->adminUrlTo( 'bar' ) );
			$this->assertSame( 'bar', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$this->route_collection->isAdmin();
			$request = TestRequest::fromFullUrl( 'POST', $this->adminUrlTo( 'bar' ) );
			$this->assertSame( null, $this->router->runRoute( $request ) );


		}

		public function adminUrlTo(string $menu_slug ) {

			$url = Url::combinePath(SITE_URL, 'wp-admin/admin.php?page=' . $menu_slug);

			return $url;

		}


	}