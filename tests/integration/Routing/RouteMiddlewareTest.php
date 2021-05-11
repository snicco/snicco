<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use PHPUnit\Framework\TestCase;
	use Tests\stubs\Middleware\BarMiddleware;
	use Tests\stubs\Middleware\BazMiddleware;
	use Tests\stubs\Middleware\FooBarMiddleware;
	use Tests\stubs\Middleware\FooMiddleware;
	use Tests\TestRequest;

	class RouteMiddlewareTest extends TestCase {

		use SetUpRouter;

		/** @test */
		public function applying_a_route_group_to_a_route_applies_all_middleware_in_the_group() {

			$this->router->middlewareGroup( 'foobar', [
				FooMiddleware::class,
				BarMiddleware::class,

			] );

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} )->middleware( 'foobar' );

			$request  = $this->request( 'GET', '/foo' );
			$response = $this->router->runRoute( $request );

			$this->assertSame( 'foobar', $response );


		}

		/** @test */
		public function middleware_in_the_global_group_is_always_applied() {


			$this->router->middlewareGroup( 'global', [
				FooMiddleware::class,
				BarMiddleware::class,
			] );

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} );

			$request  = $this->request( 'GET', '/foo' );
			$response = $this->router->runRoute( $request );

			$this->assertSame( 'foobar', $response );

		}

		/** @test */
		public function duplicate_middleware_is_filtered_out() {

			$this->router->middlewareGroup( 'global', [
				FooMiddleware::class,
				BarMiddleware::class,
			] );

			$this->router->middlewareGroup( 'foobar', [
				FooMiddleware::class,
				BarMiddleware::class,
			] );

			$this->router->middleware( 'foobar' )->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} );

			$request  = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foobar', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function duplicate_middleware_is_filtered_out_when_passing_the_same_middleware_arguments () {

			$this->router->aliasMiddleware('foo', FooMiddleware::class);
			$this->router->middlewareGroup('all', [

				FooMiddleware::class . ':FOO',
				BarMiddleware::class,
				BazMiddleware::class,

			]);

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			})->middleware(['all', 'foo:FOO']);

			$request = $this->request('GET', 'foo');
			$this->seeResponse( 'FOObarbaz', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function multiple_middleware_groups_can_be_applied() {

			$this->router->middlewareGroup( 'foo', [
				FooMiddleware::class,
			] );

			$this->router->middlewareGroup( 'bar', [
				BarMiddleware::class,
			] );

			$this->router->middleware( 'foo', 'bar' )
			             ->get( '/foo', function ( TestRequest $request ) {

				             return $request->body;

			             } );

			$request  = $this->request( 'GET', '/foo' );
			$response = $this->router->runRoute( $request );

			$this->assertSame( 'foobar', $response );

		}

		/** @test */
		public function middleware_can_be_aliased() {

			$routes = function () {

				$this->router->middleware('foo')->get( 'foo', function ( TestRequest $request ) {

					return $request->body;

				} );

			};

			$this->newRouterWith( $routes );
			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );
			$request = $this->request('GET', '/foo');
			$this->seeResponse('foo', $this->router->runRoute($request) );

			$this->expectExceptionMessage('Unknown middleware [foo]');

			$this->newRouterWith( $routes );
			$request = $this->request('GET', '/foo');
			$this->seeResponse('foo', $this->router->runRoute($request) );
		}

		/** @test */
		public function multiple_middleware_arguments_can_be_passed () {

			$this->router->aliasMiddleware('foobar', FooBarMiddleware::class);

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} )->middleware('foobar');

			$this->router->post( '/foo', function ( TestRequest $request ) {

				return $request->body;

			})->middleware('foobar:FOO');

			$this->router->patch( '/foo', function ( TestRequest $request ) {

				return $request->body;

			})->middleware('foobar:FOO,BAR');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foobar', $this->router->runRoute( $request ) );

			$request = $this->request( 'POST', '/foo' );
			$this->seeResponse( 'FOObar', $this->router->runRoute( $request ) );

			$request = $this->request( 'PATCH', '/foo' );
			$this->seeResponse( 'FOOBAR', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function a_middleware_group_can_point_to_a_middleware_alias () {

			$this->router->aliasMiddleware('foo', FooMiddleware::class);
			$this->router->middlewareGroup('foogroup', [
				'foo'
			]);

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			})->middleware('foogroup');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function group_and_route_middleware_can_be_combined () {

			$this->router->aliasMiddleware('baz', BazMiddleware::class);
			$this->router->middlewareGroup('foobar', [
				FooMiddleware::class,
				BarMiddleware::class
			]);

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} )->middleware(['baz', 'foobar']);

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'bazfoobar', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function a_middleware_group_can_contain_another_middleware_group() {

			$this->router->middlewareGroup('foo', [
				FooMiddleware::class
			]);

			$this->router->middlewareGroup('bar', [
				BarMiddleware::class,
				'foo',
			]);

			$this->router->middlewareGroup('baz', [
				BazMiddleware::class,
				'bar',
			]);

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			})->middleware('baz');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'bazbarfoo', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function middleware_can_be_applied_without_an_alias () {

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} )->middleware(FooBarMiddleware::class. ':FOO,BAR');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'FOOBAR', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function string_middleware_without_an_alias_will_raise_an_exception() {

			$this->expectExceptionMessage('Unknown middleware [foo] used.');

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} )->middleware('foo');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( null , $this->router->runRoute( $request ) );

		}


		/**
		 *
		 *
		 *
		 *
		 * SORTING
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function non_global_middleware_can_be_sorted () {


			$this->router->middlewarePriority([

				FooMiddleware::class,
				BarMiddleware::class,
				BazMiddleware::class

			]);

			$this->router->middlewareGroup('barbaz', [

				BazMiddleware::class,
				BarMiddleware::class,

			]);

			$this->router->middleware('barbaz')->group(function () {

				$this->router->get( '/foo', function ( TestRequest $request ) {

					return $request->body;

				} )->middleware(FooMiddleware::class );

			});

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foobarbaz', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function middleware_keeps_its_relative_position_if_its_has_no_priority_defined () {


			$this->router->middlewarePriority([

				FooMiddleware::class,
				BarMiddleware::class,

			]);

			$this->router->middlewareGroup('all', [

				FooBarMiddleware::class,
				BarMiddleware::class,
				BazMiddleware::class,
				FooMiddleware::class,


			]);

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} )->middleware('all');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foobarfoobarbaz', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function if_a_global_middleware_group_is_applied_its_middleware_will_be_excluded_from_the_sorting_and_always_come_first () {

			$this->router->middlewareGroup('global', [
				FooMiddleware::class,
				BarMiddleware::class,
			]);

			$this->router->middlewarePriority([

				BazMiddleware::class,
				FooBarMiddleware::class,
				BarMiddleware::class,
				FooMiddleware::class,


			]);

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} )->middleware([FooBarMiddleware::class, BazMiddleware::class]);

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foobarbazfoobar', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function all_middleware_can_be_skipped () {

			$this->router->middlewareGroup('global', [
				FooMiddleware::class,
				BarMiddleware::class,
			]);

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			} )->middleware([FooBarMiddleware::class, BazMiddleware::class]);

			$this->router->withoutMiddleware();

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( null , $this->router->runRoute( $request ) );


		}

	}