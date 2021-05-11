<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use PHPUnit\Framework\TestCase;
	use Tests\stubs\Conditions\FalseCondition;
	use WPEmerge\Contracts\RequestInterface;

	class RouteConditionsTest extends TestCase {


		use SetUpRouter;

		/**
		 *
		 *
		 *
		 *
		 *
		 *
		 * CUSTOM CONDITIONS
		 *
		 *
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function custom_conditions_can_be_added_as_strings() {

			$this->router
				->get( '/foo' )
				->where( 'false' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );


		}

		/** @test */
		public function custom_conditions_can_be_added_as_objects() {

			$this->router
				->get( '/foo' )
				->where( new FalseCondition() )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );


		}

		/** @test */
		public function custom_conditions_can_be_added_before_the_http_verb() {

			$this->router
				->where( new FalseCondition() )
				->get( '/foo' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

			$this->router
				->where( 'false' )
				->post( '/bar' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'POST', '/bar' ) ) );

		}

		/** @test */
		public function a_condition_stack_can_be_added_before_the_http_verb() {

			$this->router
				->where( function ( $foo ) {

					$GLOBALS['test']['cond1'] = $foo;

					return $foo === 'foo';

				}, 'foo' )
				->where( function ( $bar ) {

					$GLOBALS['test']['cond2'] = $bar;

					return $bar === 'bar';

				}, 'bar' )
				->get( '/baz' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/baz' ) ) );
			$this->assertSame( 'bar', $GLOBALS['test']['cond2'] );
			$this->assertSame( 'foo', $GLOBALS['test']['cond1'] ?? null, 'First condition did not execute' );


		}

		/** @test */
		public function a_closure_can_be_a_condition() {


			$this->router
				->get( '/foo' )
				->where( function () {

					return true;

				} )
				->where(
					function ( $foo, $bar ) {

						return $foo === 'foo' && $bar === 'bar';

					},
					'foo',
					'bar'
				)
				->handle(
					function ( $request, $foo, $bar ) {

						return $foo . $bar;

					}
				);

			$this->seeResponse( 'foobar', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

			$this->router
				->post( '/will-fail' )
				->where(
					function ( $foo, $bar ) {

						return $foo === 'foo' && $bar === 'bar';

					},
					'foo',
					'baz'
				)
				->handle(
					function ( $request, $foo, $bar ) {

						return $foo . $bar;

					}
				);

			$this->assertNull( $this->router->runRoute( $this->request( 'POST', '/will-fail' ) ) );

			$this->router
				->where(
					function ( $foo, $bar ) {

						return $foo === 'foo' && $bar === 'bar';

					},
					'foo',
					'bar'
				)
				->put( '/foo-before' )
				->handle(
					function ( $request, $foo, $bar ) {

						return $foo . $bar;

					}
				);

			$this->seeResponse( 'foobar', $this->router->runRoute( $this->request( 'PUT', '/foo-before' ) ) );


		}

		/** @test */
		public function multiple_conditions_and_all_conditions_have_to_pass() {

			$this->router
				->get( '/foo' )
				->where( 'true' )
				->where( 'false' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );


		}

		/** @test */
		public function a_condition_can_be_negated() {


			$this->router
				->get( '/foo' )
				->where( '!false' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

			$this->router
				->post( '/bar' )
				->where( 'negate', 'false' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'POST', '/bar' ) ) );

			$this->router
				->put( '/baz' )
				->where( 'negate', function ( $foo ) {

					return $foo !== 'foo';

				}, 'foo' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'PUT', '/baz' ) ) );


		}

		/** @test */
		public function a_condition_can_be_negated_while_passing_arguments() {

			$this->router
				->get( '/foo' )
				->where( 'maybe', true )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

			$this->router
				->post( '/bar' )
				->where( 'maybe', false )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'POST', '/bar' ) ) );

			$this->router
				->put( '/baz' )
				->where( '!maybe', false )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'PUT', '/baz' ) ) );

			$this->router
				->delete( '/baz' )
				->where( '!maybe', false )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'DELETE', '/baz' ) ) );

			$this->router
				->patch( '/foobar' )
				->where( '!maybe', 'foobar' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'PATCH', '/foobar' ) ) );


		}

		/** @test */
		public function matching_url_conditions_will_fail_if_custom_conditions_are_not_met() {


			$this->router
				->get( '/foo' )
				->where( 'maybe', false )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( null, $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );
			$this->assertTrue( $GLOBALS['test']['maybe_condition_run'] );

		}

		/** @test */
		public function a_condition_object_can_be_negated() {

			$this->router
				->get( '/foo' )
				->where( 'negate', new FalseCondition() )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );


		}

		/** @test */
		public function failure_of_only_one_condition_leads_to_immediate_rejection_of_the_route() {

			$this->router
				->get( '/foo' )
				->where( 'false' )
				->where( function () {

					$this->fail( 'This condition should not have been called.' );

				} )
				->handle( function ( RequestInterface $request ) {

					$this->fail('This should never be called.');

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

		}

		/** @test */
		public function conditions_can_be_resolved_using_the_service_container() {


			$this->router
				->where( 'dependency_condition', true )
				->get( 'foo', function () {

					return 'foo';

				} );

			$get = $this->request( 'GET', '/foo' );

			$this->seeResponse( 'foo', $this->router->runRoute( $get ) );

			$this->router
				->where( 'dependency_condition', false )
				->post( 'foo', function () {

					return 'foo';

				} );

			$post = $this->request( 'POST', '/foo' );

			$this->seeResponse( null, $this->router->runRoute( $post ) );


		}

		/** @test */
		public function global_functions_can_be_used_as_custom_conditions() {


			$this->router->where( 'is_string', 'foo' )->get( 'foo', function () {

				return 'foo';

			} );

			$get = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foo', $this->router->runRoute( $get ) );

			$this->router
				->where( 'is_string', 1 )
				->post( 'foo', function () {

					return 'foo';

				} );

			$post = $this->request( 'POST', '/foo' );

			$this->seeResponse( null, $this->router->runRoute( $post ) );

			$this->router
				->where( '!is_string', 1 )
				->put( 'foo', function () {

					return 'foo';

				} );

			$put = $this->request( 'PUT', '/foo' );

			$this->seeResponse( 'foo', $this->router->runRoute( $put ) );


		}


	}