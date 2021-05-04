<?php


	namespace Tests\integration\Routing;

	use Codeception\TestCase\WPTestCase;
	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\Foo;
	use Tests\stubs\Middleware\BarMiddleware;
	use Tests\stubs\Middleware\FooMiddleware;
	use Tests\stubs\Middleware\GlobalMiddleware;
	use Tests\TestRequest;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Requests\Request;
	use WPEmerge\Routing\Conditions\AdminCondition;
	use WPEmerge\Routing\Conditions\AjaxCondition;
	use WPEmerge\Routing\ConditionFactory;
	use WPEmerge\Routing\Conditions\CustomCondition;
	use WPEmerge\Routing\Conditions\NegateCondition;
	use WPEmerge\Routing\Conditions\PostIdCondition;
	use WPEmerge\Routing\Conditions\PostSlugCondition;
	use WPEmerge\Routing\Conditions\PostStatusCondition;
	use WPEmerge\Routing\Conditions\PostTemplateCondition;
	use WPEmerge\Routing\Conditions\PostTypeCondition;
	use WPEmerge\Routing\Conditions\QueryVarCondition;
	use WPEmerge\Routing\Conditions\UrlCondition;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;
	use WPEmerge\Support\Str;

	class RouteAttributesTest extends WPTestCase {

		use SetUpRouter;

		const controller_namespace = 'Tests\stubs\Controllers\Web';


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
				'true'                 => \Tests\stubs\Conditions\TrueCondition::class,
				'false'                => \Tests\stubs\Conditions\FalseCondition::class,
				'maybe'                => \Tests\stubs\Conditions\MaybeCondition::class,
				'unique'               => \Tests\stubs\Conditions\UniqueCondition::class,
				'dependency_condition' => \Tests\stubs\Conditions\ConditionWithDependency::class,

			];

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

			$this->router->get( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

			$response = $this->router->runRoute( $this->request( 'HEAD', '/foo' ) );

			$this->seeResponse( 'foo', $response );


		}

		/** @test */
		public function basic_post_routing_works() {

			$this->router->post( '/foo/' )->handle( function () {

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

			$this->router->any( '/foo', function () {

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

		/** @test */
		public function static_and_dynamic_routes_can_be_added_for_the_same_uri_while_static_routes_take_precedence() {

			$routes = function () {

				$this->router->post( '/foo/bar', function () {

					return 'foo_bar_static';

				} )->where( 'false' );

				$this->router->post( '/foo/baz', function () {

					return 'foo_baz_static';

				} );

				$this->router->post( '/foo/{dynamic}', function () {

					return 'dynamic_route';

				} );

			};

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'POST', '/foo/bar' ) );
			$this->seeResponse( 'dynamic_route', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'POST', '/foo/baz' ) );
			$this->seeResponse( 'foo_baz_static', $response );

			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'POST', '/foo/biz' ) );
			$this->seeResponse( 'dynamic_route', $response );

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


			$this->router->namespace( self::controller_namespace )
			             ->get( '/get1', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'GET', '/get1' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( self::controller_namespace )
			             ->post( '/post1', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'POST', '/post1' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( self::controller_namespace )
			             ->put( '/put1', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'PUT', '/put1' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( self::controller_namespace )
			             ->patch( '/patch1', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'PATCH', '/patch1' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( self::controller_namespace )
			             ->options( '/options1', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'OPTIONS', '/options1' ) );
			$this->seeResponse( 'foo', $response );

			$this->router->namespace( self::controller_namespace )
			             ->delete( '/delete1', 'RoutingController@foo' );
			$response = $this->router->runRoute( $this->request( 'DELETE', '/delete1' ) );
			$this->seeResponse( 'foo', $response );

			$routes = function () {

				$this->router->namespace( self::controller_namespace )
				             ->match( [ 'GET', 'POST' ], '/match1', 'RoutingController@foo' );


			};
			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'GET', '/match1' ) );
			$this->seeResponse( 'foo', $response );

			$routes = function () {

				$this->router->namespace( self::controller_namespace )
				             ->match( [ 'GET', 'POST' ], '/match2', 'RoutingController@foo' );

			};
			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'POST', '/match2' ) );
			$this->seeResponse( 'foo', $response );

			$routes = function () {

				$this->router->namespace( self::controller_namespace )
				             ->match( [ 'GET', 'POST' ], '/match3', 'RoutingController@foo' );

			};
			$this->newRouterWith( $routes );
			$response = $this->router->runRoute( $this->request( 'PUT', '/match3' ) );
			$this->seeResponse( null, $response );

		}

		/** @test */
		public function a_route_namespace_can_be_set() {

			$this->router
				->get( '/foo' )
				->namespace( self::controller_namespace )
				->handle( 'RoutingController@foo' );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function a_route_namespace_can_be_set_before_the_http_verb() {

			$this->router
				->namespace( self::controller_namespace )
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
				->post( '/bar' )
				->middleware( 'bar' )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$this->router->middlewareGroup( 'global', [ GlobalMiddleware::class ] );
			$this->router->aliasMiddleware( 'bar', BarMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'POST', '/bar' ) );
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
				->post( '/bar' )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$response = $this->router->runRoute( $this->request( 'POST', '/bar' ) );
			$this->seeResponse( 'foobar', $response );

			// With Args
			$this->router
				->middleware( [ 'foo:FOO', 'bar:BAR' ] )
				->put( '/baz' )
				->handle( function ( RequestInterface $request ) {

					return $request->body;

				} );

			$response = $this->router->runRoute( $this->request( 'PUT', '/baz' ) );
			$this->seeResponse( 'FOOBAR', $response );


		}








	}

