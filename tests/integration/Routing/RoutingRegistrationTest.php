<?php


	namespace Tests\integration\Routing;

	use Codeception\TestCase\WPTestCase;
	use Mockery as m;
	use PHPUnit\Framework\TestCase;
	use Psr\Http\Message\ResponseInterface;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\TestViewService;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Requests\Request;
	use WPEmerge\Routing\Conditions\AdminCondition;
	use WPEmerge\Routing\Conditions\AjaxCondition;
	use WPEmerge\Routing\Conditions\ConditionFactory;
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
	use WPEmerge\Routing\RouteBlueprint;
	use WPEmerge\Routing\Router;
	use WPEmerge\Support\Str;
	use WPEmerge\View\ViewService;

	class RoutingRegistrationTest extends WPTestCase {


		/**
		 * @var \SniccoAdapter\BaseContainerAdapter|
		 */
		private $container;

		/**
		 * @var \WPEmerge\Routing\Conditions\ConditionFactory
		 */
		private $condition_factory;

		/**
		 * @var \WPEmerge\Handlers\HandlerFactory
		 */
		private $handler_factory;

		/**
		 * @var \WPEmerge\Routing\Router
		 */
		private $router;


		protected function setUp() : void {

			parent::setUp();

			$this->container         = new BaseContainerAdapter();
			$this->condition_factory = new ConditionFactory( $this->conditions(), $this->container );
			$this->handler_factory   = new HandlerFactory( [], $this->container );
			$this->router            = $this->newRouter();

		}

		protected function tearDown() : void {

			m::close();

			parent::tearDown();
		}

		private function conditions() : array {

			return [

				'url'           => UrlCondition::class,
				'custom'        => CustomCondition::class,
				'multiple'      => MultipleCondition::class,
				'negate'        => NegateCondition::class,
				'post_id'       => PostIdCondition::class,
				'post_slug'     => PostSlugCondition::class,
				'post_status'   => PostStatusCondition::class,
				'post_template' => PostTemplateCondition::class,
				'post_type'     => PostTypeCondition::class,
				'query_var'     => QueryVarCondition::class,
				'ajax'          => AjaxCondition::class,
				'admin'         => AdminCondition::class,
				'model'         => ModelCondition::class,
				'true'          => TrueCondition::class,
				'false'         => FalseCondition::class,

			];

		}

		private function newRouter() : Router {

			$this->router = new Router(
				$this->container, $this->condition_factory, $this->handler_factory
			);

			return $this->router;

		}

		private function request( $method, $url ) {

			$request = m::mock( Request::class );
			$request->shouldReceive( 'getMethod' )->andReturn( strtoupper( $method ) );
			$request->shouldReceive( 'getUrl' )->andReturn( 'https://foo.com' . $url );
			$request->shouldReceive( 'setRoute' )
			        ->andReturnUsing( function ( RouteInterface $route ) use ( $request ) {

				        $request->test_route = $route;
			        } );

			return $request;

		}

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

		/** @test */
		public function regex_can_be_added_as_a_condition_without_needing_array_syntax () {


			$this->router->get('users/{user}', function () {

				return 'foo';

			})->where('user', '/[0-9]+/');

			$request = $this->request('GET', '/users/1');
			$this->seeResponse('foo', $this->router->runRoute($request));

			$request = $this->request('GET', '/users/calvin');
			$this->assertNull($this->router->runRoute($request));


		}

		/** @test */
		public function regex_can_be_added_as_a_condition_as_array_syntax () {


			$this->router->get('users/{user}', function () {

				return 'foo';

			})->where(['user', '/[0-9]+/']);

			$request = $this->request('GET', '/users/1');
			$this->seeResponse('foo', $this->router->runRoute($request));

			$request = $this->request('GET', '/users/calvin');
			$this->assertNull($this->router->runRoute($request));


		}

		/** @test */
		public function multiple_regex_conditions_can_be_added_to_an_url_condition () {


			$this->router->get('/user/{id}/{name}', function ( Request $request, $id, $name) {

				return $name . $id;

			})->where(['id' => '/[0-9]+/', 'name' => '/[a-z]+/']);

			$request = $this->request('GET', '/user/1/calvin');
			$this->seeResponse('calvin1', $this->router->runRoute($request));

			$request = $this->request('GET', '/users/calvin/1');
			$this->assertNull($this->router->runRoute($request));

		}



		// /**
		//  * @todo refactor this to an internal view controller.
		//  */
		// public function view_routes_work() {
		//
		// 	$this->newBluePrint()->view( '/welcome', 'welcome', [ 'name' => 'Calvin' ] );
		//
		// 	$view = $this->router->runRoute( $this->request( 'GET', '/welcome' ) );
		//
		// 	$this->assertInstanceOf( ViewInterface::class, $view );
		//
		// 	$this->assertSame( 'welcome', $view->view );
		//
		// 	$this->newBluePrint()->view( '/welcome', 'welcome', [ 'name' => 'Calvin' ] );
		//
		// 	$view = $this->router->runRoute( $this->request( 'POST', '/welcome' ) );
		//
		// 	$this->assertNull( $view );
		//
		//
		// }
		//

		// /**
		//  *
		//  *
		//  *
		//  *
		//  * TEST FOR ADDITIONAL NON REGEX CONDITIONS
		//  *
		//  *
		//  *
		//  *
		//  *
		//  */
		//
		// /** @test */
		// public function conditions_can_be_chained_and_they_all_need_to_pass_to_match_the_route() {
		//
		// 	$this->router
		// 	     ->get( '/foo' )
		// 	     ->where( 'false' )
		// 	     ->handle( function ( RequestInterface $request ) {
		//
		// 		     return 'foo';
		//
		// 	     } );
		//
		// 	$this->assertNull(
		// 		$this->router->runRoute( $this->request( 'GET', '/foo' )
		// 		) );
		//
		// 	$this->newBluePrint()
		// 	     ->get( '/foo' )
		// 	     ->where( 'false' )
		// 	     ->where( 'true' )
		// 	     ->handle( function ( RequestInterface $request ) {
		//
		// 		     return 'foo';
		//
		//
		// 	     } );
		//
		// 	$this->assertNull(
		// 		$this->router->runRoute( $this->request( 'GET', '/foo' ) )
		// 	);
		//
		// 	$this->newBluePrint()
		// 	     ->get( '/foo' )
		// 	     ->where( 'true' )
		// 	     ->handle( function ( RequestInterface $request ) {
		//
		// 		     return 'foo';
		//
		// 	     } );
		//
		// 	$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );
		//
		//
		// }
		//
		// /** @test */
		// public function a_closure_can_be_a_condition() {
		//
		//
		// 	$this->newBluePrint()
		// 	     ->get( '/foo' )
		// 	     ->where(
		// 		     function ( $foo, $bar ) {
		//
		// 			     return $foo = 'foo' && $bar = 'bar';
		//
		// 		     },
		// 		     'foo',
		// 		     'bar'
		// 	     )
		// 	     ->handle(
		// 		     function ( $request, $foo, $bar ) {
		//
		// 			     return $foo . $bar;
		//
		// 		     }
		// 	     );
		//
		// 	$this->seeResponse( 'foobar', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );
		//
		// }
		//
		// /** @test */
		// public function a_condition_can_be_negated() {
		//
		//
		// 	$this->newBluePrint()
		// 	     ->get( '/foo' )
		// 	     ->where( '!false' )
		// 	     ->handle( function ( RequestInterface $request ) {
		//
		// 		     return 'foo';
		//
		// 	     } );
		//
		// 	$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );
		//
		// 	$this->newBluePrint()
		// 	     ->get( '/foo' )
		// 	     ->where( 'negate', 'false' )
		// 	     ->handle( function ( RequestInterface $request ) {
		//
		// 		     return 'foo';
		//
		// 	     } );
		//
		// 	$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );
		//
		//
		// }
		//
		// /** @test */
		// public function a_condition_object_can_be_negated() {
		//
		// 	$this->newBluePrint()
		// 	     ->get( '/foo' )
		// 	     ->where( 'negate', new FalseCondition() )
		// 	     ->handle( function ( RequestInterface $request ) {
		//
		// 		     return 'foo';
		//
		// 	     } );
		//
		// 	$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );
		//
		//
		// }
		//
		// /** @test */
		// public function an_anonymous_closure_can_be_a_negated_condition() {
		//
		// 	$this->newBluePrint()
		// 	     ->get( '/foo' )
		// 	     ->where( 'negate', function ( $foo ) {
		//
		// 		     return $foo === 'foo';
		// 	     }, 'foo' )
		// 	     ->handle( function ( RequestInterface $request ) {
		//
		// 		     return 'foo';
		//
		// 	     } );
		//
		// 	$this->assertNull( $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );
		//
		// }
		//
		//
		// /**
		//  *
		//  *
		//  *
		//  * ROUTE GROUPS
		//  *
		//  *
		//  *
		//  */
		//
		// /** @test */
		// public function methods_are_merged_for_route_groups() {
		//
		// 	$this->newBluePrint()
		// 	     ->methods( [ 'GET', 'PUT' ] )
		// 	     ->group( function () {
		//
		// 		     $this->blueprint->post( '/foo' )->handle( function () {
		//
		// 			     return 'post_foo';
		//
		// 		     } );
		//
		// 	     } );
		//
		// 	$get_request = $this->request( 'GET', '/foo' );
		// 	$response    = $this->router->runRoute( $get_request );
		// 	$this->seeResponse( 'post_foo', $response );
		//
		// 	$put_request = $this->request( 'PUT', '/foo' );
		// 	$response    = $this->router->runRoute( $put_request );
		// 	$this->seeResponse( 'post_foo', $response );
		//
		// 	$post_request = $this->request( 'POST', '/foo' );
		// 	$response     = $this->router->runRoute( $post_request );
		// 	$this->seeResponse( 'post_foo', $response );
		//
		//
		// }
		//
		// /** @test */
		// public function middleware_is_merged_for_route_groups() {
		//
		//
		// 	$blueprint = $this->newBluePrint();
		// 	$this->router->aliasMiddleware( 'foo', FooMiddleware::class );
		// 	$this->router->aliasMiddleware( 'bar', BarMiddleware::class );
		//
		// 	$blueprint
		// 		->middleware( 'foo:FOO' )
		// 		->group( function () {
		//
		// 			$this->newBluePrint( $this->router )
		// 			     ->get( '/foo' )
		// 			     ->middleware( 'bar:BAR' )
		// 			     ->handle( function ( RequestInterface $request ) {
		//
		// 				     return $request->body;
		//
		// 			     } );
		//
		// 			$this->newBluePrint( $this->router )
		// 			     ->post( '/foo' )
		// 			     ->handle( function ( RequestInterface $request ) {
		//
		// 				     return $request->body;
		//
		// 			     } );
		//
		// 		} );
		//
		// 	$get_request = $this->request( 'GET', '/foo' );
		// 	$response    = $this->router->runRoute( $get_request );
		// 	$this->seeResponse( 'FOOBAR', $response );
		//
		// 	$post_request = $this->request( 'POST', '/foo' );
		// 	$response     = $this->router->runRoute( $post_request );
		// 	$this->seeResponse( 'FOO', $response );
		//
		//
		// }
		//
		// /** @test */
		// public function the_group_namespace_is_applied_to_child_routes() {
		//
		// 	$this->newBluePrint()
		// 	     ->namespace( 'Tests\integration\Routing' )
		// 	     ->group( function () {
		//
		// 		     $this->blueprint->get( '/foo' )->handle( 'RoutingController@foo' );
		//
		// 	     } );
		//
		// 	$get_request = $this->request( 'GET', '/foo' );
		// 	$response    = $this->router->runRoute( $get_request );
		// 	$this->seeResponse( 'foo', $response );
		//
		//
		// }
		//
		// /** @test */
		// public function group_conditions_are_merged() {
		//
		// 	$this->newBluePrint()
		// 	     ->where( 'true' )
		// 	     ->namespace( 'Tests\integration\Routing' )
		// 	     ->group( function () {
		//
		// 		     $this->newBluePrint( $this->router )
		// 		          ->get( '/foo' )
		// 		          ->where( new FalseCondition() )
		// 		          ->handle( 'RoutingController@foo' );
		//
		// 		     $this->newBluePrint( $this->router )
		// 		          ->post( '/foo' )
		// 		          ->where( new TrueCondition() )
		// 		          ->handle( 'RoutingController@foo' );
		//
		// 	     } );
		//
		// 	$get_request = $this->request( 'GET', '/foo' );
		// 	$response    = $this->router->runRoute( $get_request );
		// 	$this->assertNull( $response );
		//
		// 	$post_request = $this->request( 'POST', '/foo' );
		// 	$response     = $this->router->runRoute( $post_request );
		// 	$this->seeResponse( 'foo', $response );
		//
		// }
		//
		// /** @test */
		// public function group_urls_are_stacked() {
		//
		//
		// 	$this->newBluePrint()
		// 	     ->namespace( 'Tests\integration\Routing' )
		// 	     ->prefix( 'foo' )
		// 	     ->group( function () {
		//
		// 		     $this->newBluePrint( $this->router )->get( 'bar' )
		// 		          ->handle( 'RoutingController@foo' );
		//
		// 	     } );
		//
		// 	$get_request = $this->request( 'GET', '/foo' );
		// 	$response    = $this->router->runRoute( $get_request );
		// 	$this->assertNull( $response );
		//
		// 	$get_request = $this->request( 'GET', '/foo/bar' );
		// 	$response    = $this->router->runRoute( $get_request );
		// 	$this->seeResponse( 'foo', $response );
		//
		//
		// }
		//
		// /** @test */
		// public function group_urls_are_stacked_on_multiple_levels() {
		//
		//
		// 	$this->newBluePrint()
		// 	     ->namespace( 'Tests\integration\Routing' )
		// 	     ->prefix( 'foo' )
		// 	     ->group( function () {
		//
		// 		     $this->newBluePrint( $this->router )->prefix( 'bar' )->group( function () {
		//
		// 			     $this->newBluePrint( $this->router )->get( 'baz' )
		// 			          ->handle( 'RoutingController@foo' );
		//
		//
		// 		     } );
		//
		// 		     $this->newBluePrint( $this->router )->get( 'biz' )
		// 		          ->handle( 'RoutingController@foo' );
		//
		// 	     } );
		//
		// 	$get_request = $this->request( 'GET', '/foo' );
		// 	$this->assertNull( $this->router->runRoute( $get_request ) );
		//
		// 	$get_request = $this->request( 'GET', '/foo/bar' );
		// 	$this->assertNull( $this->router->runRoute( $get_request ) );
		//
		// 	$get_request = $this->request( 'GET', '/foo/biz' );
		// 	$this->seeResponse( 'foo', $this->router->runRoute( $get_request ) );
		//
		// 	$get_request = $this->request( 'GET', '/foo/bar/baz' );
		// 	$this->seeResponse( 'foo', $this->router->runRoute( $get_request ) );
		//
		// }
		//
		// /**
		//  *
		//  *
		//  *
		//  *
		//  * Named routes.
		//  *
		//  *
		//  *
		//  *
		//  */
		//
		// /** @test */
		// public function a_named_route_can_be_created() {
		//
		// 	$this->newBluePrint()
		// 	     ->get( 'foo' )
		// 	     ->name( 'foo' )
		// 	     ->handle( function () {
		//
		// 		     return 'foo';
		//
		// 	     } );
		//
		// 	$url = $this->router->getRouteUrl( 'foo' );
		//
		// 	$this->seeUrl( 'foo', $url );
		//
		// }
		//
		//
		// /** @test */
		// public function route_groups_prefix_the_named_paths() {
		//
		//
		// 	$this->newBluePrint()
		// 	     ->name( 'foo' )
		// 	     ->get( 'foo' )
		// 	     ->group( function () {
		//
		// 		     $this->newBluePrint( $this->router )->get( 'bar' )->name( 'bar' )
		// 		          ->handle( function () {
		// 		          } );
		//
		// 		     $this->newBluePrint( $this->router )->get( 'baz' )->name( 'baz' )
		// 		          ->handle( function () {
		// 		          } );
		//
		// 		     $this->newBluePrint( $this->router )->name( 'biz' )->get( 'biz' )
		// 		          ->group( function () {
		//
		// 			          $this->newBluePrint( $this->router )->get( 'boo' )->name( 'boo' )
		// 			               ->handle( function () {
		// 			               } );
		//
		// 		          } );
		//
		// 	     } );
		//
		// 	$this->seeUrl( 'foo/bar', $this->router->getRouteUrl( 'foo.bar' ) );
		// 	$this->seeUrl( 'foo/baz', $this->router->getRouteUrl( 'foo.baz' ) );
		// 	$this->seeUrl( 'foo/biz/boo', $this->router->getRouteUrl( 'foo.biz.boo' ) );
		//
		//
		// }

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