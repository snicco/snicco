<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use PHPUnit\Framework\TestCase;
	use Tests\stubs\Conditions\FalseCondition;
	use Tests\stubs\Conditions\TrueCondition;
	use Tests\stubs\Conditions\UniqueCondition;
	use Tests\stubs\Middleware\BarMiddleware;
	use Tests\stubs\Middleware\BazMiddleware;
	use Tests\stubs\Middleware\FooMiddleware;
	use WPEmerge\Contracts\RequestInterface;

	class RouteGroupsTest extends TestCase {

		use SetUpRouter;

		const namespace = 'Tests\stubs\Controllers\Web';

		/**
		 *
		 *
		 *
		 *
		 *
		 * ROUTE GROUPS
		 *
		 *
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function methods_can_be_merged_for_a_group() {

			$this->router
				->methods( [ 'GET', 'PUT' ] )
				->group( function () {

					$this->router->post( '/foo' )->handle( function () {

						return 'post_foo';

					} );

				} );

			$get_request = $this->request( 'GET', '/foo' );
			$response    = $this->router->runRoute( $get_request );
			$this->seeResponse( 'post_foo', $response );

			$put_request = $this->request( 'PUT', '/foo' );
			$response    = $this->router->runRoute( $put_request );
			$this->seeResponse( 'post_foo', $response );

			$post_request = $this->request( 'POST', '/foo' );
			$response     = $this->router->runRoute( $post_request );
			$this->seeResponse( 'post_foo', $response );

			$patch_request = $this->request( 'PATCH', '/foo' );
			$response      = $this->router->runRoute( $patch_request );
			$this->assertNull( $response );


		}

		/** @test */
		public function middleware_is_merged_for_route_groups() {


			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );
			$this->router->aliasMiddleware( 'bar', BarMiddleware::class );

			$this->router
				->middleware( 'foo:FOO' )
				->group( function () {

					$this->router
						->get( '/foo' )
						->middleware( 'bar:BAR' )
						->handle( function ( RequestInterface $request ) {

							return $request->body;

						} );

					$this->router
						->post( '/foo' )
						->handle( function ( RequestInterface $request ) {

							return $request->body;

						} );

				} );

			$get_request = $this->request( 'GET', '/foo' );
			$response    = $this->router->runRoute( $get_request );
			$this->seeResponse( 'FOOBAR', $response );

			$post_request = $this->request( 'POST', '/foo' );
			$response     = $this->router->runRoute( $post_request );
			$this->seeResponse( 'FOO', $response );


		}

		/** @test */
		public function the_group_namespace_is_applied_to_child_routes() {

			$this->router
				->namespace( self::namespace )
				->group( function () {

					$this->router->get( '/foo' )->handle( 'RoutingController@foo' );

				});

			$get_request = $this->request( 'GET', '/foo' );
			$response    = $this->router->runRoute( $get_request );
			$this->seeResponse( 'foo', $response );


		}

		/** @test */
		public function a_group_can_prefix_all_child_route_urls() {

			$routes = function () {

				$this->router
					->prefix( 'foo' )
					->group( function () {

						$this->router->get( 'bar', function () {

							return 'foobar';

						} );

						$this->router->get( 'baz', function () {

							return 'foobaz';

						} );


					} );

			};

			$this->newRouterWith( $routes );
			$this->seeResponse( 'foobar', $this->router->runRoute( $this->request( 'GET', '/foo/bar' ) ) );

			$this->newRouterWith( $routes );
			$this->seeResponse( 'foobaz', $this->router->runRoute( $this->request( 'GET', '/foo/baz' ) ) );

			$this->newRouterWith( $routes );
			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

		}

		/** @test */
		public function group_conditions_are_merged_into_child_routes() {

			$this->router
				->where( 'maybe', false )
				->namespace( 'Tests\stubs\Controllers\Web' )
				->group( function () {

					$this->router
						->get( '/foo' )
						->where( new FalseCondition() )
						->handle( 'RoutingController@foo' );

					$this->router
						->post( '/foo' )
						->where( new TrueCondition() )
						->handle( 'RoutingController@foo' );

				} );

			$get_request = $this->request( 'GET', '/foo' );
			$response    = $this->router->runRoute( $get_request );
			$this->assertNull( $response );

			$post_request = $this->request( 'POST', '/foo' );
			$response     = $this->router->runRoute( $post_request );
			$this->assertNull( $response );

		}

		/** @test */
		public function duplicate_conditions_a_removed_during_route_compilation() {

			$this->router
				->where( new UniqueCondition() )
				->group( function () {

					$this->router
						->get( '/foo', function () {

							return 'get_foo';

						} )
						->where( new UniqueCondition() );

				} );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );
			$this->seeResponse( 'get_foo', $response );

			$count = $GLOBALS['test']['unique_condition'];
			$this->assertSame( 1, $count, 'Condition was run: ' . $count . ' times.' );


		}

		/** @test */
		public function unique_conditions_are_also_enforced_when_conditions_are_aliased() {

			$this->router
				->where( 'unique' )
				->group( function () {

					$this->router
						->get( '/bar', function () {

							return 'get_bar';

						} )
						->where( 'unique' );

				} );

			$response = $this->router->runRoute( $this->request( 'GET', '/bar' ) );
			$this->seeResponse( 'get_bar', $response );

			$count = $GLOBALS['test']['unique_condition'];
			$this->assertSame( 1, $count, 'Condition was run: ' . $count . ' times.' );


		}

		/**
		 *
		 *
		 *
		 *
		 *
		 * NESTED ROUTE GROUPS
		 *
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function methods_are_merged_on_multiple_levels() {

			$routes = function () {

				$this->router
					->methods( 'GET' )
					->group( function () {

						$this->router->methods( 'POST' )->group( function () {

							$this->router->put( '/foo' )->handle( function () {

								return 'foo';

							} );

						} );

						$this->router->patch( '/bar' )->handle( function () {

							return 'bar';

						} );

					} );

			};

			// First route
			$this->newRouterWith( $routes );
			$post     = $this->request( 'POST', '/foo' );
			$response = $this->router->runRoute( $post );
			$this->seeResponse( 'foo', $response );

			$put      = $this->request( 'PUT', '/foo' );
			$response = $this->router->runRoute( $put );
			$this->seeResponse( 'foo', $response );

			$get      = $this->request( 'GET', '/foo' );
			$response = $this->router->runRoute( $get );
			$this->seeResponse( 'foo', $response );

			$patch    = $this->request( 'PATCH', '/foo' );
			$response = $this->router->runRoute( $patch );
			$this->assertNull( $response );

			// Second route
			$this->newRouterWith( $routes );
			$get      = $this->request( 'GET', '/bar' );
			$response = $this->router->runRoute( $get );
			$this->seeResponse( 'bar', $response );

			$patch    = $this->request( 'PATCH', '/bar' );
			$response = $this->router->runRoute( $patch );
			$this->seeResponse( 'bar', $response );

			$post     = $this->request( 'POST', '/bar' );
			$response = $this->router->runRoute( $post );
			$this->seeResponse( null, $response );

			$put      = $this->request( 'PUT', '/bar' );
			$response = $this->router->runRoute( $put );
			$this->seeResponse( null, $response );

		}

		/** @test */
		public function middleware_is_nested_on_multiple_levels() {


			$routes = function () {

				$this->router
					->middleware( 'foo:FOO' )
					->group( function () {

						$this->router->middleware( 'bar:BAR' )->group( function () {

							$this->router
								->get( '/foo' )
								->middleware( 'baz:BAZ' )
								->handle( function ( RequestInterface $request ) {

									return $request->body;

								} );

						} );

						$this->router
							->get( '/bar' )
							->middleware( 'baz:BAZ' )
							->handle( function ( RequestInterface $request ) {

								return $request->body;

							} );

					} );

				$this->router->aliasMiddleware( 'foo', FooMiddleware::class );
				$this->router->aliasMiddleware( 'bar', BarMiddleware::class );
				$this->router->aliasMiddleware( 'baz', BazMiddleware::class );

			};

			$this->newRouterWith( $routes );
			$get      = $this->request( 'GET', '/foo' );
			$response = $this->router->runRoute( $get );
			$this->seeResponse( 'FOOBARBAZ', $response );

			$this->newRouterWith( $routes );
			$get      = $this->request( 'GET', '/bar' );
			$response = $this->router->runRoute( $get );
			$this->seeResponse( 'FOOBAZ', $response );

		}

		/** @test */
		public function the_route_namespace_is_always_overwritten_by_child_routes() {

			/** @todo decide if this is desired. */
			$this->router
				->namespace( 'Tests\FalseNamespace' )
				->group( function () {

					$this->router
						->namespace( self::namespace )
						->get( '/foo' )
						->handle( 'RoutingController@foo' );

				} );

			$get_request = $this->request( 'GET', '/foo' );
			$response    = $this->router->runRoute( $get_request );
			$this->seeResponse( 'foo', $response );


		}

		/** @test */
		public function group_prefixes_are_merged_on_multiple_levels() {

			$routes = function () {

				$this->router
					->prefix( 'foo' )
					->group( function () {

						$this->router->prefix( 'bar' )->group( function () {

							$this->router->get( 'baz', function () {

								return 'foobarbaz';

							} );

						} );

						$this->router->get( 'biz', function () {

							return 'foobiz';

						} );


					} );


			};

			$this->newRouterWith( $routes );
			$this->seeResponse( 'foobarbaz', $this->router->runRoute( $this->request( 'GET', '/foo/bar/baz' ) ) );

			$this->newRouterWith( $routes );
			$this->seeResponse( 'foobiz', $this->router->runRoute( $this->request( 'GET', '/foo/biz' ) ) );

			$this->newRouterWith( $routes );
			$this->seeResponse( null, $this->router->runRoute( $this->request( 'GET', '/foo/bar/biz' ) ) );


		}

		/** @test */
		public function conditions_are_merged_on_multiple_levels() {

			// Given
			$GLOBALS['test']['parent_condition_called'] = false;
			$GLOBALS['test']['child_condition_called']  = false;

			$routes = function () {

				$this->router
					->where( function () {

						$GLOBALS['test']['parent_condition_called'] = true;

						$this->assertFalse( $GLOBALS['test']['child_condition_called'] );

						return true;

					} )
					->group( function () {

						$this->router
							->get( '/bar' )
							->where( 'true' )
							->handle( function () {

								return 'bar';

							} );

						$this->router->where( function () {

							$GLOBALS['test']['child_condition_called'] = true;

							return false;

						} )->group( function () {

							$this->router
								->get( '/foo' )
								->where( 'true' )
								->handle( function () {

									$this->fail( 'This route should not have been called' );

								} );

						} );


					} );

			};


			// When
			$this->newRouterWith( $routes );
			$get      = $this->request( 'GET', '/foo' );
			$response = $this->router->runRoute( $get );

			// Then
			$this->seeResponse( null, $response );
			$this->assertSame( true, $GLOBALS['test']['parent_condition_called'] );
			$this->assertSame( true, $GLOBALS['test']['child_condition_called'] );

			// Given
			$GLOBALS['test']['parent_condition_called'] = false;
			$GLOBALS['test']['child_condition_called']  = false;

			// When
			$this->newRouterWith( $routes );
			$get      = $this->request( 'GET', '/bar' );
			$response = $this->router->runRoute( $get );

			// Then
			$this->seeResponse( 'bar', $response );
			$this->assertSame( true, $GLOBALS['test']['parent_condition_called'] );
			$this->assertSame( false, $GLOBALS['test']['child_condition_called'] );


		}

		/** @test */
		public function the_first_matching_route_aborts_the_iteration_over_all_current_routes() {

			$GLOBALS['test']['first_route_condition'] = false;

			$this->router->prefix( 'foo' )->group( function () {

				$this->router
					->get( '/bar' )
					->where( function () {

						$GLOBALS['test']['first_route_condition'] = true;

						return true;

					} )
					->handle( function () {

						return 'bar1';

					} );

				$this->router
					->get( '/{bar}' )
					->where( function () {

						$this->fail( 'Route condition evaluated even tho we already had a matching route' );

					} )
					->handle( function () {

						return 'bar2';

					} );


			} );

			$this->seeResponse(
				'bar1',
				$this->router->runRoute(
					$this->request( 'GET', '/foo/bar' )
				)
			);

			$this->assertTrue( $GLOBALS['test']['first_route_condition'] );

		}

		/** @test */
		public function url_conditions_are_passed_even_if_one_group_in_the_chain_does_not_specify_an_url_condition() {

			$routes = function () {

				$this->router->prefix( 'foo' )->group( function () {

					$this->router->where( 'true' )->group( function () {

						$this->router->get( 'bar', function () {

							return 'foobar';

						} );

					} );

				} );

			};

			$this->newRouterWith( $routes );
			$get = $this->request( 'GET', '/foo/bar' );
			$this->seeResponse( 'foobar', $this->router->runRoute( $get ) );

			$this->newRouterWith( $routes );
			$get = $this->request( 'GET', '/foo' );
			$this->seeResponse( null, $this->router->runRoute( $get ) );


		}

		/** @test */
		public function url_conditions_are_passed_even_if_the_root_group_doesnt_specify_an_url_condition() {

			$routes = function () {

				$this->router->where( 'true' )->group( function () {

					$this->router->prefix( 'foo' )->group( function () {

						$this->router->get( 'bar', function () {

							return 'foobar';

						} );

					} );

				} );


			};

			$this->newRouterWith( $routes );
			$get = $this->request( 'GET', '/foo/bar' );
			$this->seeResponse( 'foobar', $this->router->runRoute( $get ) );

			$this->newRouterWith( $routes );
			$get = $this->request( 'GET', '/foo' );
			$this->seeResponse( null, $this->router->runRoute( $get ) );


		}

	}

