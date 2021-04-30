<?php


	namespace Tests\integration\Routing;

	use Codeception\TestCase\WPTestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\Foo;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Requests\Request;
	use WPEmerge\Routing\Conditions\AdminCondition;
	use WPEmerge\Routing\Conditions\AjaxCondition;
	use WPEmerge\Routing\ConditionFactory;
	use WPEmerge\Routing\Conditions\CustomCondition;
	use WPEmerge\Routing\Conditions\ModelCondition;
	use WPEmerge\Routing\Conditions\MultipleCondition;
	use WPEmerge\Routing\Conditions\NegateCondition;
	use WPEmerge\Routing\Conditions\PostIdCondition;
	use WPEmerge\Routing\Conditions\PostSlugCondition;
	use WPEmerge\Routing\Conditions\PostStatusCondition;
	use WPEmerge\Routing\Conditions\PostTemplateCondition;
	use WPEmerge\Routing\Conditions\PostTypeCondition;
	use WPEmerge\Routing\Conditions\QueryVarCondition;
	use WPEmerge\Routing\Conditions\UrlCondition;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;
	use WPEmerge\Support\Str;

	class RoutingRegistrationTest extends WPTestCase {


		/**
		 * @var \WPEmerge\Routing\Router
		 */
		private $router;


		protected function setUp() : void {

			parent::setUp();

			$container         = new BaseContainerAdapter();
			$condition_factory = new ConditionFactory( $this->conditions(), $container );
			$handler_factory   = new HandlerFactory( [], $container );
			$route_collection  = new RouteCollection( $condition_factory, $handler_factory );
			$this->router      = new Router( $container, $route_collection );

			unset( $GLOBALS['test'] );

		}

		protected function tearDown() : void {


			parent::tearDown();

			unset( $GLOBALS['test'] );
		}

		private function conditions() : array {

			return [

				'url'                  => UrlCondition::class,
				'custom'               => CustomCondition::class,
				'negate'               => NegateCondition::class,
				'post_id'              => PostIdCondition::class,
				'post_slug'            => PostSlugCondition::class,
				'post_status'          => PostStatusCondition::class,
				'post_template'        => PostTemplateCondition::class,
				'post_type'            => PostTypeCondition::class,
				'query_var'            => QueryVarCondition::class,
				'ajax'                 => AjaxCondition::class,
				'admin'                => AdminCondition::class,
				'true'                 => TrueCondition::class,
				'false'                => FalseCondition::class,
				'maybe'                => MaybeCondition::class,
				'unique'               => UniqueCondition::class,
				'dependency_condition' => ConditionWithDependency::class,

			];

		}

		private function request( $method, $url ) {

			return new TestRequest( strtoupper( $method ), 'https://foo.com' . $url );

		}

		/**
		 * @todo refactor this to an internal view controller.
		 */
		public function view_routes_work() {

			// $this->newBluePrint()->view( '/welcome', 'welcome', [ 'name' => 'Calvin' ] );
			//
			// $view = $this->router->runRoute( $this->request( 'GET', '/welcome' ) );
			//
			// $this->assertInstanceOf( ViewInterface::class, $view );
			//
			// $this->assertSame( 'welcome', $view->view );
			//
			// $this->newBluePrint()->view( '/welcome', 'welcome', [ 'name' => 'Calvin' ] );
			//
			// $view = $this->router->runRoute( $this->request( 'POST', '/welcome' ) );
			//
			// $this->assertNull( $view );

		}


		/**
		 *
		 *
		 *
		 *
		 * BASIC ROUTING
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function basic_get_routing_works() {

			$this->router->get( 'foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

			$this->router->get( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'HEAD', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_post_routing_works() {

			$this->router->post( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'post', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_put_routing_works() {

			$this->router->put( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'put', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_patch_routing_works() {

			$this->router->patch( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'patch', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_delete_routing_works() {

			$this->router->delete( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'delete', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_options_routing_works() {

			$this->router->options( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'options', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function a_route_can_match_all_methods() {

			$this->router->any( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'get', '/foo' ) );
			$this->seeResponse( 'foo', $response );

			$response = $this->router->runRoute( $this->request( 'post', '/foo' ) );
			$this->seeResponse( 'foo', $response );

			$response = $this->router->runRoute( $this->request( 'put', '/foo' ) );
			$this->seeResponse( 'foo', $response );

			$response = $this->router->runRoute( $this->request( 'patch', '/foo' ) );
			$this->seeResponse( 'foo', $response );

			$response = $this->router->runRoute( $this->request( 'delete', '/foo' ) );
			$this->seeResponse( 'foo', $response );

			$response = $this->router->runRoute( $this->request( 'options', '/foo' ) );
			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function a_route_can_match_specific_methods() {

			$this->router->match( [ 'GET', 'POST' ], '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'get', '/foo' ) );
			$this->seeResponse( 'foo', $response );

			$response = $this->router->runRoute( $this->request( 'post', '/foo' ) );
			$this->seeResponse( 'foo', $response );

			$this->assertNull( $this->router->runRoute( $this->request( 'put', '/foo' ) ) );

		}

		/** @test */
		public function the_route_handler_can_be_defined_in_the_http_verb_method() {

			$this->router->get( 'foo', function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}


		/**
		 *
		 *
		 *
		 *
		 *
		 * ROUTE ATTRIBUTES
		 *
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function all_http_verbs_can_be_defined_after_attributes_and_finalize_the_route() {


			$this->router->namespace( 'Tests\Integration\Routing' )
			             ->get( '/get', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'GET', '/get' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( 'Tests\Integration\Routing' )
			             ->post( '/post', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'POST', '/post' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( 'Tests\Integration\Routing' )
			             ->put( '/put', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'PUT', '/put' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( 'Tests\Integration\Routing' )
			             ->patch( '/patch', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'PATCH', '/patch' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( 'Tests\Integration\Routing' )
			             ->options( '/options', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'OPTIONS', '/options' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( 'Tests\Integration\Routing' )
			             ->delete( '/delete', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'DELETE', '/delete' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( 'Tests\Integration\Routing' )
			             ->match( [ 'GET', 'POST' ], '/match', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'GET', '/match' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( 'Tests\Integration\Routing' )
			             ->match( [ 'GET', 'POST' ], '/match', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'POST', '/match' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( 'Tests\Integration\Routing' )
			             ->match( [ 'GET', 'POST' ], '/match', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'PUT', '/match' ) );
			$this->assertNull( $response );


		}

		/** @test */
		public function a_route_namespace_can_be_set() {

			$this->router
				->get( '/foo' )
				->namespace( 'Tests\Integration\Routing' )
				->handle( 'RoutingController@foo' );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function a_route_namespace_can_be_set_before_the_http_verb() {

			$this->router
				->namespace( 'Tests\Integration\Routing' )
				->get( '/foo' )
				->handle( 'RoutingController@foo' );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function middleware_can_be_set() {

			$this->router
				->get( '/foo' )
				->middleware( 'foo' )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function a_route_can_have_multiple_middleware() {

			$this->router
				->get( '/foo' )
				->middleware( [ 'foo', 'bar' ] )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );
			$this->router->aliasMiddleware( 'bar', BarMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foobar', $response );


		}

		/** @test */
		public function middleware_can_pass_arguments() {

			$this->router
				->get( '/foo' )
				->middleware( [ 'foo:foofoo', 'bar:barbar' ] )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );
			$this->router->aliasMiddleware( 'bar', BarMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foofoobarbar', $response );

		}

		/** @test */
		public function global_middleware_gets_merged_for_all_routes_if_set() {

			$this->router
				->get( '/foo' )
				->middleware( 'foo' )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$this->router->middlewareGroup( 'global', [ GlobalMiddleware::class ] );
			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );
			$this->seeResponse( 'global_foo', $response );

			$this->router
				->get( '/bar' )
				->middleware( 'bar' )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$this->router->middlewareGroup( 'global', [ GlobalMiddleware::class ] );
			$this->router->aliasMiddleware( 'bar', BarMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'GET', '/bar' ) );
			$this->seeResponse( 'global_bar', $response );


		}

		/** @test */
		public function middleware_can_be_set_before_the_http_verb() {

			$this->router
				->middleware( 'foo' )
				->get( '/foo' )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );
			$this->router->aliasMiddleware( 'bar', BarMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

			// As array.
			$this->router
				->middleware( [ 'foo', 'bar' ] )
				->get( '/bar' )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$response = $this->router->runRoute( $this->request( 'GET', '/bar' ) );
			$this->seeResponse( 'foobar', $response );

			// With Args
			$this->router
				->middleware( [ 'foo:FOO', 'bar:BAR' ] )
				->get( '/baz' )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$response = $this->router->runRoute( $this->request( 'GET', '/baz' ) );
			$this->seeResponse( 'FOOBAR', $response );


		}

		/** @test */
		public function a_route_can_be_named() {


			$this->router->get( 'foo' )->name( 'foo_route' );
			$url = $this->router->getRouteUrl( 'foo_route' );
			$this->seeUrl( 'foo', $url );

			$this->router->name( 'bar_route' )->get( 'bar' );
			$url = $this->router->getRouteUrl( 'bar_route' );
			$this->seeUrl( 'bar', $url );


		}

		/**
		 *
		 *
		 *
		 *
		 *
		 * REGEX CONDITIONS
		 *
		 *
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function regex_can_be_added_as_a_condition_without_needing_array_syntax() {


			$this->router->get( 'users/{user}', function () {

				return 'foo';

			} )->where( 'user', '/[0-9]+/' );

			$request = $this->request( 'GET', '/users/1' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$request = $this->request( 'GET', '/users/calvin' );
			$this->assertNull( $this->router->runRoute( $request ) );


		}

		/** @test */
		public function regex_can_be_added_as_a_condition_as_array_syntax() {


			$this->router->get( 'users/{user}', function () {

				return 'foo';

			} )->where( [ 'user', '/[0-9]+/' ] );

			$request = $this->request( 'GET', '/users/1' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$request = $this->request( 'GET', '/users/calvin' );
			$this->assertNull( $this->router->runRoute( $request ) );


		}

		/** @test */
		public function multiple_regex_conditions_can_be_added_to_an_url_condition() {


			$this->router->get( '/user/{id}/{name}', function ( Request $request, $id, $name ) {

				return $name . $id;

			} )->where( [ 'id' => '/[0-9]+/', 'name' => '/[a-z]+/' ] );

			$request = $this->request( 'GET', '/user/1/calvin' );
			$this->seeResponse( 'calvin1', $this->router->runRoute( $request ) );

			$request = $this->request( 'GET', '/users/calvin/1' );
			$this->assertNull( $this->router->runRoute( $request ) );

		}

		/** @test */
		public function regex_can_be_added_before_the_http_verb() {

			$this->router->where( 'user', '/[0-9]+/' )->get( 'users/{user}', function () {

				return 'foo';

			} );

			$request = $this->request( 'GET', '/users/1' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$request = $this->request( 'GET', '/users/calvin' );
			$this->assertNull( $this->router->runRoute( $request ) );

		}

		/** @test */
		public function regex_in_array_format_can_be_added_before_the_http_verb() {

			$this->router->where( [ 'user', '/[0-9]+/' ] )->get( 'users/{user}', function () {

				return 'foo';

			} );

			$request = $this->request( 'GET', '/users/1' );
			$this->seeResponse( 'foo', $this->router->runRoute( $request ) );

			$request = $this->request( 'GET', '/users/calvin' );
			$this->assertNull( $this->router->runRoute( $request ) );

		}

		/** @test */
		public function multiple_regex_conditions_can_be_added_before_the_http_verb() {

			$this->router->where( [ 'id' => '/[0-9]+/', 'name' => '/[a-z]+/' ] )
			             ->get( '/user/{id}/{name}', function ( Request $request, $id, $name ) {

				             return $name . $id;

			             } );

			$request = $this->request( 'GET', '/user/1/calvin' );
			$this->seeResponse( 'calvin1', $this->router->runRoute( $request ) );

			$request = $this->request( 'GET', '/users/calvin/1' );
			$this->assertNull( $this->router->runRoute( $request ) );

		}

		/** @test */
		public function optional_parameters_work_in_the_url_work_with_regex() {

			$this->router->get( 'foo/{bar?}/{baz}', function ( Request $request, $bar, $baz ) {

				return $bar . $baz;

			} )->where( 'baz', '/[0-9]+/' );

			$request = $this->request( 'GET', '/foo/bar/1' );
			$this->seeResponse( 'bar1', $this->router->runRoute( $request ) );

			$request = $this->request( 'GET', '/foo/bar/abc' );
			$this->assertNull( $this->router->runRoute( $request ) );


		}


		/** @test */
		public function unmatched_optional_parameters_get_passed_as_null_values_and_dont_break_the_route() {

			$this->router->get( 'foo/{bar?}/{baz}', function ( Request $request, $bar, $baz ) {

				$this->assertSame( '', $bar );

				return $baz;

			} )->where( 'baz', '/[0-9]+/' );

			$request = $this->request( 'GET', '/foo/1' );
			$this->seeResponse( '1', $this->router->runRoute( $request ) );

			$request = $this->request( 'GET', '/foo/bar/abc' );
			$this->assertNull( $this->router->runRoute( $request ) );

			$request = $this->request( 'GET', '/foo/abc' );
			$this->assertNull( $this->router->runRoute( $request ) );

		}

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
				->get( '/bar' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/bar' ) ) );

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
				->get( '/will-fail' )
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

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/will-fail' ) ) );

			$this->router
				->where(
					function ( $foo, $bar ) {

						return $foo === 'foo' && $bar === 'bar';

					},
					'foo',
					'bar'
				)
				->get( '/foo-before' )
				->handle(
					function ( $request, $foo, $bar ) {

						return $foo . $bar;

					}
				);

			$this->seeResponse( 'foobar', $this->router->runRoute( $this->request( 'GET', '/foo-before' ) ) );


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
				->get( '/bar' )
				->where( 'negate', 'false' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/bar' ) ) );

			$this->router
				->get( '/baz' )
				->where( 'negate', function ( $foo ) {

					return $foo !== 'foo';

				}, 'foo' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/baz' ) ) );


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
				->get( '/bar' )
				->where( 'maybe', false )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/bar' ) ) );

			$this->router
				->get( '/baz' )
				->where( '!maybe', false )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/baz' ) ) );

			$this->router
				->get( '/baz' )
				->where( '!maybe', false )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/baz' ) ) );

			$this->router
				->get( '/foobar' )
				->where( '!maybe', 'foobar' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/foobar' ) ) );


		}

		/** @test */
		public function negated_conditions_have_precedence_over_regex() {

			// This should result in two conditions.
			// Not on regex being added to the url condition
			// /foobar/ will make the condition fail.
			$this->router
				->get( '/foo' )
				->where( '!maybe', '/foobar/' )
				->handle( function ( RequestInterface $request ) {

					return 'foo';

				} );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );
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

					return 'foo';

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

			$this->seeResponse('foo', $this->router->runRoute( $get ) );

			$this->router
				->where( 'dependency_condition', false )
				->post( 'foo', function () {

					return 'foo';

				} );

			$get = $this->request( 'POST', '/foo' );

			$this->seeResponse(null , $this->router->runRoute( $get ) );




		}

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
		public function the_group_namespace_is_applied_to_child_routes_but_they_might_overwrite_it() {

			$this->router
				->namespace( 'Tests\integration\Routing' )
				->group( function () {

					$this->router->get( '/foo' )->handle( 'RoutingController@foo' );

				} );

			$get_request = $this->request( 'GET', '/foo' );
			$response    = $this->router->runRoute( $get_request );
			$this->seeResponse( 'foo', $response );


		}

		/** @test */
		public function group_names_get_applied_to_child_routes() {

			$this->router
				->name( 'foo' )
				->group( function () {

					$this->router->get( 'bar' )->name( 'bar' );

					$this->router->get( 'baz' )->name( 'baz' );

					$this->router->name( 'biz' )->get( 'biz' );

				} );

			$this->seeUrl( 'bar', $this->router->getRouteUrl( 'foo.bar' ) );
			$this->seeUrl( 'baz', $this->router->getRouteUrl( 'foo.baz' ) );
			$this->seeUrl( 'biz', $this->router->getRouteUrl( 'foo.biz' ) );


		}

		/** @test */
		public function a_group_can_prefix_all_child_route_urls() {

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

			$this->seeResponse( 'foobar', $this->router->runRoute( $this->request( 'GET', '/foo/bar' ) ) );
			$this->seeResponse( 'foobaz', $this->router->runRoute( $this->request( 'GET', '/foo/baz' ) ) );

			$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

		}

		/** @test */
		public function group_conditions_are_merged_into_child_routes() {

			$this->router
				->where( 'maybe', false )
				->namespace( 'Tests\integration\Routing' )
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

			// First route
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


			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );
			$this->router->aliasMiddleware( 'bar', BarMiddleware::class );
			$this->router->aliasMiddleware( 'baz', BazMiddleware::class );

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

			$get      = $this->request( 'GET', '/foo' );
			$response = $this->router->runRoute( $get );
			$this->seeResponse( 'FOOBARBAZ', $response );

			$get      = $this->request( 'GET', '/bar' );
			$response = $this->router->runRoute( $get );
			$this->seeResponse( 'FOOBAZ', $response );

		}

		/** @test */
		public function the_route_namespace_is_always_overwritten_by_child_routes() {

			$this->router
				->namespace( 'Tests\integration\FALSE' )
				->group( function () {

					$this->router
						->namespace( 'Tests\integration\Routing' )
						->get( '/foo' )
						->handle( 'RoutingController@foo' );

				} );

			$get_request = $this->request( 'GET', '/foo' );
			$response    = $this->router->runRoute( $get_request );
			$this->seeResponse( 'foo', $response );


		}

		/** @test */
		public function route_names_are_merged_on_multiple_levels() {

			$this->router
				->name( 'foo' )
				->group( function () {

					$this->router->name( 'bar' )->group( function () {

						$this->router->get( 'baz' )->name( 'baz' );

					} );

					$this->router->get( 'biz' )->name( 'biz' );


				} );

			$this->seeUrl( 'baz', $this->router->getRouteUrl( 'foo.bar.baz' ) );
			$this->seeUrl( 'biz', $this->router->getRouteUrl( 'foo.biz' ) );

			$this->expectExceptionMessage( 'no named route' );

			$this->seeUrl( 'baz', $this->router->getRouteUrl( 'foo.bar.biz' ) );


		}

		/** @test */
		public function group_prefixes_are_merged_on_multiple_levels() {

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

			$this->seeResponse( 'foobarbaz', $this->router->runRoute( $this->request( 'GET', '/foo/bar/baz' ) ) );
			$this->seeResponse( 'foobiz', $this->router->runRoute( $this->request( 'GET', '/foo/biz' ) ) );
			$this->seeResponse( null, $this->router->runRoute( $this->request( 'GET', '/foo/bar/biz' ) ) );


		}

		/** @test */
		public function conditions_are_merged_on_multiple_levels() {

			// Given
			$GLOBALS['test']['parent_condition_called'] = false;
			$GLOBALS['test']['child_condition_called']  = false;

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

			// When
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
					->get( '/bar' )
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


			$this->router->prefix( 'foo' )->group( function () {

				$this->router->where( 'true' )->group( function () {

					$this->router->get( 'bar', function () {

						return 'foobar';

					} );

				} );

			} );

			$get = $this->request( 'GET', '/foo/bar' );

			$this->seeResponse( 'foobar', $this->router->runRoute( $get ) );

			$get = $this->request( 'GET', '/foo' );

			$this->seeResponse( null, $this->router->runRoute( $get ) );


		}

		/** @test */
		public function url_conditions_are_passed_even_if_the_root_group_doesnt_specify_an_url_condition() {

			$this->router->where( 'true' )->group( function () {

				$this->router->prefix( 'foo' )->group( function () {

					$this->router->get( 'bar', function () {

						return 'foobar';

					} );

				} );

			} );

			$get = $this->request( 'GET', '/foo/bar' );

			$this->seeResponse( 'foobar', $this->router->runRoute( $get ) );

			$get = $this->request( 'GET', '/foo' );

			$this->seeResponse( null, $this->router->runRoute( $get ) );


		}

		private function seeResponse( $expected, $response ) {

			$this->assertSame( $expected, $response );

		}

		private function seeUrl( $route_path, $result ) {

			$expected = SITE_URL . $route_path;

			// Strip https, http
			$expected = Str::after( $expected, '://' );
			$result   = Str::after( $result, '://' );

			$this->assertSame( $expected, $result );

		}

	}


	class RoutingController {

		public function foo( Request $request ) {

			return 'foo';

		}

	}


	class FooMiddleware implements Middleware {

		public function handle( RequestInterface $request, \Closure $next, $foo = 'foo' ) {

			if ( isset( $request->body ) ) {

				$request->body .= $foo;

				return $next( $request );
			}

			$request->body = $foo;

			return $next( $request );


		}

	}


	class BarMiddleware implements Middleware {

		public function handle( RequestInterface $request, \Closure $next, $bar = 'bar' ) {

			if ( isset( $request->body ) ) {

				$request->body .= $bar;

				return $next( $request );
			}

			$request->body = $bar;

			return $next( $request );

		}

	}


	class BazMiddleware implements Middleware {

		public function handle( RequestInterface $request, \Closure $next, $baz = 'baz' ) {

			if ( isset( $request->body ) ) {

				$request->body .= $baz;

				return $next( $request );
			}

			$request->body = $baz;

			return $next( $request );

		}

	}


	class GlobalMiddleware {


		public function handle( RequestInterface $request, \Closure $next ) {

			if ( isset( $request->body ) ) {

				$request->body .= 'global_';

				return $next( $request );
			}

			$request->body = 'global_';

			return $next( $request );


		}


	}


	class TrueCondition implements ConditionInterface {


		public function isSatisfied( RequestInterface $request ) {

			return true;
		}

		public function getArguments( RequestInterface $request ) {

			return [];

		}

	}


	class FalseCondition implements ConditionInterface {


		public function isSatisfied( RequestInterface $request ) {

			return false;
		}

		public function getArguments( RequestInterface $request ) {

			return [];

		}

	}


	class MaybeCondition implements ConditionInterface {

		/**
		 * @var bool
		 */
		private $make_it_pass;

		public function __construct( $make_it_pass ) {

			$this->make_it_pass = $make_it_pass;

		}

		public function isSatisfied( RequestInterface $request ) {

			$GLOBALS['test']['maybe_condition_run'] = true;

			return $this->make_it_pass === true || $this->make_it_pass === 'foobar';
		}

		public function getArguments( RequestInterface $request ) {

			return [];

		}

	}


	class UniqueCondition implements ConditionInterface {


		public function isSatisfied( RequestInterface $request ) : bool {

			$count = $GLOBALS['test']['unique_condition'] ?? 0;

			$count ++;

			$GLOBALS['test']['unique_condition'] = $count;

			return true;

		}

		public function getArguments( RequestInterface $request ) : array {

			return [];

		}

	}


	class ConditionWithDependency implements ConditionInterface {


		/**
		 * @var bool
		 */
		private $make_it_pass;

		/**
		 * @var \Tests\stubs\Foo
		 */
		private $foo;

		public function __construct( $make_it_pass, Foo $foo ) {

			$this->make_it_pass = $make_it_pass;
			$this->foo          = $foo;

		}

		public function isSatisfied( RequestInterface $request ) : bool {

			if ( ! isset( $this->foo ) ) {

				return false;

			}

			return $this->make_it_pass === true || $this->make_it_pass === 'foobar';
		}

		public function getArguments( RequestInterface $request ) {

			return [];

		}

	}


	class TestRequest extends Request {

		public function __construct(
			$method,
			$uri,
			array $headers = [],
			$body = null,
			$version = '1.1',
			array $serverParams = []
		) {

			parent::__construct( $method, $uri, $headers, $body, $version, $serverParams );

		}


	}