<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use Tests\RequestTesting;
    use Tests\TestCase;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Facade\WP;

	class AdminRoutesTest extends TestCase {

		use SetUpRouter;
        use RequestTesting;

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

			$request = $this->adminRequestTo('foo');

			$this->assertSame( 'foo', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function routes_to_different_admin_pages_dont_match() {


			$this->router->group( [ 'prefix' => 'wp-admin/admin.php' ], function () {

				$this->router->get( 'foo', function () {

					return 'foo';
				} );

			} );

			$request = $this->adminRequestTo('bar');

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

			$request = $this->adminRequestTo('foo');

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
			$request = $this->adminRequestTo('foo');
			$this->assertSame( 'foo', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->adminRequestTo('bar');
			$this->assertSame( 'bar', $this->router->runRoute( $request ) );

			$this->newRouterWith( $routes );
			$request = $this->adminRequestTo('baz', 'POST');
			$this->assertSame( null, $this->router->runRoute( $request ) );


		}

		/** @test */
        public function reverse_routing_works_with_admin_routes () {

            WP::shouldReceive('pluginPageUrl')->andReturnUsing(function ($page) {

                return $this->adminUrlTo($page);

            });

            $this->router->group( [ 'prefix' => 'wp-admin/admin.php', 'name' => 'admin' ] , function () {

                $this->router->get( 'foo', function ( RequestInterface $request, string $page ) {

                    return $page;

                })->name('foo');

            });

            $url = $this->router->getRouteUrl('admin.foo');

            $this->assertSame($this->adminUrlTo('foo'), $url);


        }




	}