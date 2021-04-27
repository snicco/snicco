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
	use WPEmerge\View\ViewService;

	class RoutingTest extends WPTestCase {


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

		private function blueprint() : RouteBlueprint {

			return new RouteBlueprint(
				$this->newRouter(), new TestViewService()
			);
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

			$this->blueprint()->get( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

			$this->blueprint()->get( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'HEAD', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_post_routing_works() {

			$this->blueprint()->post( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'post', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_put_routing_works() {

			$this->blueprint()->put( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'put', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_patch_routing_works() {

			$this->blueprint()->patch( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'patch', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_delete_routing_works() {

			$this->blueprint()->delete( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'delete', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function basic_options_routing_works() {

			$this->blueprint()->options( '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'options', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function a_route_can_match_all_methods() {

			$this->blueprint()->any( '/foo' )->handle( function () {

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

			$this->blueprint()->match( [ 'GET', 'POST' ], '/foo' )->handle( function () {

				return 'foo';

			} );

			$response = $this->router->runRoute( $this->request( 'get', '/foo' ) );
			$this->seeResponse( 'foo', $response );

			$response = $this->router->runRoute( $this->request( 'post', '/foo' ) );
			$this->seeResponse( 'foo', $response );

			$this->assertNull( $this->router->runRoute( $this->request( 'put', '/foo' ) ) );

		}

		/** @test */
		public function view_routes_work() {

			$this->blueprint()->view( '/welcome', 'welcome', [ 'name' => 'Calvin' ] );

			$view = $this->router->runRoute( $this->request( 'GET', '/welcome' ) );

			$this->assertInstanceOf( ViewInterface::class, $view );

			$this->assertSame( 'welcome', $view->view );

			$this->blueprint()->view( '/welcome', 'welcome', [ 'name' => 'Calvin' ] );

			$view = $this->router->runRoute( $this->request( 'POST', '/welcome' ) );

			$this->assertNull( $view );


		}

		/** @test */
		public function a_route_namespace_can_be_set() {

			$this->blueprint()
			     ->namespace( 'Tests\Integration\Routing' )
			     ->get( '/foo' )
			     ->handle( 'RoutingController@foo' );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function middleware_can_be_set() {

			$this->blueprint()
			     ->middleware( 'foo' )
			     ->get( '/foo' )
			     ->handle( function ( RequestInterface $request ) {

				     return $request->body;

			     } );

			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );

			$this->seeResponse( 'foo', $response );

		}

		/** @test */
		public function a_route_can_have_multiple_middleware() {

			$this->blueprint()
			     ->middleware( [ 'foo', 'bar' ] )
			     ->get( '/foo' )
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

			$this->blueprint()
			     ->middleware( [ 'foo:foofoo', 'bar:barbar' ] )
			     ->get( '/foo' )
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

			$this->blueprint()
			     ->middleware( 'foo' )
			     ->get( '/foo' )
			     ->handle( function ( RequestInterface $request ) {

				     return $request->body;

			     } );

			$this->router->middlewareGroup( 'global', [ GlobalMiddleware::class ] );
			$this->router->aliasMiddleware( 'foo', FooMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );
			$this->seeResponse( 'global_foo', $response );

			$this->blueprint()
			     ->middleware( 'bar' )
			     ->get( '/foo' )
			     ->handle( function ( RequestInterface $request ) {

				     return $request->body;

			     } );

			$this->router->middlewareGroup( 'global', [ GlobalMiddleware::class ] );
			$this->router->aliasMiddleware( 'bar', BarMiddleware::class );

			$response = $this->router->runRoute( $this->request( 'GET', '/foo' ) );
			$this->seeResponse( 'global_bar', $response );


		}


		/** @test */
		public function conditions_can_be_chained_and_they_all_need_to_pass_to_match_the_route() {

			$this->blueprint()
			     ->get( '/foo' )
			     ->where( 'false' )
			     ->handle( function ( RequestInterface $request ) {

				     return 'foo';

			     } );

			$this->assertNull(
				$this->router->runRoute( $this->request( 'GET', '/foo' )
				));

			$this->blueprint()
			     ->get( '/foo' )
			     ->where( 'false' )
			     ->where( 'true' )
			     ->handle( function ( RequestInterface $request ) {

				     return 'foo';


			     } );

			$this->assertNull(
				$this->router->runRoute( $this->request( 'GET', '/foo' ) )
			);

			$this->blueprint()
			     ->get( '/foo' )
			     ->where( 'true' )
			     ->handle( function ( RequestInterface $request ) {

				     return 'foo';

			     } );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );


		}

		/** @test */
		public function a_closure_can_be_a_condition () {

			$this->blueprint()->get()
			    ->where(
				    function( $foo, $bar ) {
					    // $foo === 'lorem'
					    // $bar === 'ipsum'
					    return true;
				    },
				    'lorem',
				    'ipsum'
			    )
			    ->handle(
				    function( $request, $view, $foo, $bar ) {
					    // $foo and $bar are available here as well.
				    }
			    );

			$this->blueprint()
			     ->get( '/foo' )
				->where(
					function( $foo, $bar ) {
						// $foo === 'lorem'
						// $bar === 'ipsum'
						return true;
					},
					'lorem',
					'ipsum'
				)
				->handle(
					function( $request, $view, $foo, $bar ) {
						// $foo and $bar are available here as well.
					}
				);

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

		}

		/** @test */
		public function a_condition_can_be_negated() {


			$this->blueprint()
			     ->get( '/foo' )
			     ->where( '!false' )
			     ->handle( function ( RequestInterface $request ) {

				     return 'foo';

			     } );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

			$this->blueprint()
			     ->get( '/foo' )
			     ->where( 'negate' , 'false' )
			     ->handle( function ( RequestInterface $request ) {

				     return 'foo';

			     } );

			$this->seeResponse('foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );


		}

		/** @test */
		public function a_condition_object_can_be_negated () {

			$this->blueprint()
			     ->get( '/foo' )
			     ->where( 'negate', new FalseCondition() )
			     ->handle( function ( RequestInterface $request ) {

				     return 'foo';

			     } );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );


		}

		/** @test */
		public function an_anonymous_closure_can_be_used_with_a_negate_condition () {

			$this->blueprint()
			     ->get( '/foo' )
			     ->where( 'negate', function () {
				     return false;
			     } )
			     ->handle( function ( RequestInterface $request ) {

				     return 'foo';

			     } );

			$this->seeResponse( 'foo', $this->router->runRoute( $this->request( 'GET', '/foo' ) ) );

		}

		private function seeResponse( $expected, $response ) {


			$this->assertSame( $expected, $response );

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