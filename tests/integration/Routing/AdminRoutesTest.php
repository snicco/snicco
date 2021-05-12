<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use Tests\TestCase;
	use Tests\TestRequest;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Facade\WP;
	use WPEmerge\Support\Url;

	class AdminRoutesTest extends TestCase {

		use SetUpRouter;

		protected function afterSetUp() {

			WP::shouldReceive('isAdmin')->andReturnTrue();

		}

		/** @test */
		public function routes_in_an_admin_group_match_without_needing_to_specify_the_full_path() {


			$this->router->group( [ 'prefix' => 'wp-admin/admin.php' ] , function () {

				$this->router->get( 'foo', function ( RequestInterface $request, string $page ) {

					return $page;
				} );

			});

			$request = $this->requestTo('foo');

			$this->assertSame( 'foo', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function routes_to_different_admin_pages_dont_match() {


			$this->router->group( [ 'prefix' => 'wp-admin/admin.php' ], function () {

				$this->router->get( 'foo', function () {

					return 'foo';
				} );

			} );

			$request = $this->requestTo('bar');

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

			$request = $this->requestTo('foo');

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
			$request = $this->requestTo('foo');
			$this->assertSame( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->requestTo('bar');
			$this->assertSame( 'bar', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->requestTo('baz', 'POST');
			$this->assertSame( null, $this->router->runRoute( $request ) );


		}

		private function adminUrlTo(string $menu_slug ) : string {

			$url = Url::combinePath(SITE_URL, 'wp-admin/admin.php?page=' . $menu_slug);

			return $url;

		}

        private function requestTo(string $admin_page, string $method = 'GET' ) : TestRequest {

            $request = TestRequest::fromFullUrl( $method, $this->adminUrlTo( $admin_page ) );

            $request->server->set('SCRIPT_FILENAME', ROOT_DIR . DS. 'wp-admin' . DS . 'admin.php');
            $request->server->set('SCRIPT_NAME', DS. 'wp-admin' . DS . 'admin.php' );
            $request->overrideGlobals();

            return $request;

        }

	}