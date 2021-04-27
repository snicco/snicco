<?php


	namespace Tests\unit\Routing;

	use Mockery as m;
	use PHPUnit\Framework\TestCase;
	use WPEmerge\Routing\RouteBlueprint;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Routing\Router;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\View\ViewService;


	class RouteBlueprintTest extends TestCase {

		public $router;

		public $view_service;

		/** @var RouteBlueprint */
		public $blueprint;

		public function setUp() : void {

			parent::setUp();

			$this->router       = m::mock( Router::class )->shouldIgnoreMissing();
			$this->view_service = m::mock( ViewService::class );
			$this->blueprint    = m::mock( RouteBlueprint::class, [
				$this->router,
				$this->view_service,
			] )->makePartial();
		}

		public function tearDown() : void {

			m::close();

			parent::tearDown();

		}

		/**
		 * @covers ::setAttributes
		 * @covers ::getAttributes
		 */
		public function testSetAttributes() {

			$expected = [ 'foo' => 'bar' ];
			$this->blueprint->setAttributes( $expected );
			$this->assertEquals( $expected, $this->blueprint->getAttributes() );
		}

		/**
		 * @covers ::setAttribute
		 * @covers ::getAttribute
		 */
		public function testSetAttribute() {

			$this->blueprint->setAttribute( 'foo', 'bar' );
			$this->assertEquals( 'bar', $this->blueprint->getAttribute( 'foo' ) );
		}

		/**
		 * @covers ::methods
		 */
		public function testMethods() {

			$router  = m::mock( Router::class )->makePartial();
			$subject = m::mock( RouteBlueprint::class, [ $router, $this->view_service ] )
			            ->makePartial();

			$this->assertSame( $subject, $subject->methods( [ 'foo' ] ) );
			$this->assertEquals( [ 'foo' ], $subject->getAttribute( 'methods' ) );

			$this->assertSame( $subject, $subject->methods( [ 'bar' ] ) );
			$this->assertEquals( [ 'foo', 'bar' ], $subject->getAttribute( 'methods' ) );
		}

		/**
		 * @covers ::url
		 */
		public function testUrl() {



			$this->router->shouldReceive( 'mergeConditionAttribute' )
			             // ->withSomeOfArgs( '', [ 'url', 'foo', [ 'bar' => 'baz' ] ] )
			             ->andReturn( 'condition' )
			             ->twice();

 			$this->assertSame( $this->blueprint, $this->blueprint->url( 'foo', [ 'bar' => 'baz' ] ) );

 			$this->blueprint->handle('');

			$this->assertEquals( 'condition', $this->blueprint->getAttribute( 'condition' ) );

		}

		/**
		 * @covers ::where
		 */
		public function testWhere_String_ConvertedToArraySyntax() {

			$this->router->shouldReceive( 'mergeConditionAttribute' )
			             ->with( '', [ 'foo', 'bar', 'baz' ] )
			             ->once();

			$this->assertSame( $this->blueprint, $this->blueprint->where( 'foo', 'bar', 'baz' ) );

			$this->assertTrue( true );
		}

		/**
		 * @covers ::where
		 */
		public function testWhere_String_StringAttribute() {

			$this->router->shouldReceive( 'mergeConditionAttribute' )
			             ->andReturn( 'foo' )
			             ->once();

			$this->assertSame( $this->blueprint, $this->blueprint->where( 'foo' ) );

			$this->assertEquals( 'foo', $this->blueprint->getAttribute( 'condition' ) );
		}

		/**
		 * @covers ::where
		 */
		public function testWhere_Null_NullAttribute() {

			$this->router->shouldReceive( 'mergeConditionAttribute' )
			             ->andReturn( null )
			             ->once();

			$this->assertSame( $this->blueprint, $this->blueprint->where( null ) );

			$this->assertNull( $this->blueprint->getAttribute( 'condition' ) );
		}

		/**
		 * @covers ::middleware
		 */
		public function testMiddleware() {

			$router  = m::mock( Router::class )->makePartial();
			$subject = m::mock( RouteBlueprint::class, [ $router, $this->view_service ] )
			            ->makePartial();

			$this->assertSame( $subject, $subject->middleware( [ 'foo' ] ) );
			$this->assertEquals( [ 'foo' ], $subject->getAttribute( 'middleware' ) );

			$this->assertSame( $subject, $subject->middleware( [ 'bar' ] ) );
			$this->assertEquals( [ 'foo', 'bar' ], $subject->getAttribute( 'middleware' ) );
		}

		/**
		 * @covers ::namespace
		 */
		public function testSetNamespace() {

			$router  = m::mock( Router::class )->makePartial();
			$subject = m::mock( RouteBlueprint::class, [ $router, $this->view_service ] )
			            ->makePartial();

			$this->assertSame( $subject, $subject->setNamespace( 'foo' ) );
			$this->assertEquals( 'foo', $subject->getAttribute( 'namespace' ) );

			$this->assertSame( $subject, $subject->setNamespace( 'bar' ) );
			$this->assertEquals( 'bar', $subject->getAttribute( 'namespace' ) );
		}



		/**
		 * @covers ::name
		 */
		public function testName() {

			$this->assertSame( $this->blueprint, $this->blueprint->name( 'foo' ) );
			$this->assertEquals( 'foo', $this->blueprint->getAttribute( 'name' ) );

			$this->assertSame( $this->blueprint, $this->blueprint->name( 'bar' ) );
			$this->assertEquals( 'bar', $this->blueprint->getAttribute( 'name' ) );
		}

		/**
		 * @covers ::group
		 */
		public function testGroup() {

			$attributes = [ 'foo' => 'bar' ];
			$routes     = function () {
			};

			$this->blueprint->setAttributes( $attributes );

			$this->router->shouldReceive( 'group' )
			             ->with( $attributes, $routes )
			             ->once();

			$this->blueprint->group( $routes );

			$this->assertTrue( true );
		}

		/**
		 * @covers ::handle
		 */
		public function testHandle_Handler_SetHandlerAttribute() {

			$this->router->shouldReceive( 'route' )
			             ->andReturn( m::mock( RouteInterface::class )
			                           ->shouldIgnoreMissing() );

			$this->blueprint->handle( 'foo' );

			$this->assertEquals( 'foo', $this->blueprint->getAttribute( 'handler' ) );
		}

		/**
		 * @covers ::handle
		 */
		public function testHandle_EmptyHandler_PassAttributes() {

			$attributes = [ 'foo' => 'bar' ];
			$route      = m::mock( RouteInterface::class )->shouldIgnoreMissing();

			$this->router->shouldReceive( 'route' )
			             ->with( $attributes )
			             ->andReturn( $route )
			             ->once();

			$this->blueprint->setAttributes( $attributes );

			$this->blueprint->handle();

			$this->assertTrue( true );
		}

		/**
		 * @covers ::handle
		 */
		public function testHandle_EmptyHandler_AddRouteToRouter() {

			$route = m::mock( RouteInterface::class )->shouldIgnoreMissing();

			$this->router->shouldReceive( 'route' )
			             ->andReturn( $route );

			$this->router->shouldReceive( 'addRoute' )
			             ->with( $route )
			             ->once();

			$this->blueprint->handle();

			$this->assertTrue( true );
		}

		/**
		 * @covers ::handle
		 */
		public function testHandle_EmptyHandler_ReturnRoute() {

			$route = m::mock( RouteInterface::class )->shouldIgnoreMissing();

			$this->router->shouldReceive( 'route' )
			             ->andReturn( $route );

			$this->router->shouldReceive( 'addRoute' )
			             ->with( $route )
			             ->once();

			$this->blueprint->handle();

			$this->assertTrue( true );
		}

		/**
		 * @covers ::view
		 */
		public function testView() {

			$view_name = 'foo';
			$view      = m::mock( ViewInterface::class );
			$handler   = null;

			$this->view_service->shouldReceive( 'make' )
			                   ->with( $view_name )
			                   ->andReturn( $view )
			                   ->once();

			$this->blueprint->shouldReceive( 'handle' )
			                ->andReturnUsing( function ( $handler ) {

				              return $handler();
			              } )
			                ->once();

			$this->blueprint->view( $view_name );

			$this->assertTrue( true );
		}

		/**
		 * @covers ::all
		 */
		public function testAll() {

			$handler = 'foo';
			$route   = m::mock( RouteInterface::class )->shouldIgnoreMissing();

			$this->router->shouldReceive( 'mergeMethodsAttribute' )
			             ->with( [], [
				             'GET',
				             'HEAD',
				             'POST',
				             'PUT',
				             'PATCH',
				             'DELETE',
				             'OPTIONS',
			             ] )
			             ->andReturn( [
				             'GET',
				             'HEAD',
				             'POST',
				             'PUT',
				             'PATCH',
				             'DELETE',
				             'OPTIONS',
			             ] );

			$this->router->shouldReceive( 'mergeConditionAttribute' )
			             ->with( '', [ 'url', '*', [] ] )
			             ->andReturn( '*' );

			$this->router->shouldReceive( 'route' )->withAnyArgs()
			             // ->withArgs( [
				         //     'handler'   => $handler,
				         //     'methods'   => [
					     //         'GET',
					     //         'HEAD',
					     //         'POST',
					     //         'PUT',
					     //         'PATCH',
					     //         'DELETE',
					     //         'OPTIONS',
				         //     ],
				         //     'condition' => '*',
			             // ] )
			             ->andReturn( $route )
			             ->once();

			$this->router->shouldReceive( 'addRoute' )
			             ->with( $route )
			             ->once();


			$this->blueprint->all( $handler );

			$this->assertTrue( true );
		}

		/**
		 * @covers ::get
		 * @covers ::post
		 * @covers ::put
		 * @covers ::patch
		 * @covers ::delete
		 * @covers ::options
		 * @covers ::any
		 */
		public function testMethodShortcuts() {

			$router = m::mock( Router::class )->makePartial();

			$subject = new RouteBlueprint( $router, $this->view_service );
			$subject->get();
			$this->assertEquals( [ 'GET', 'HEAD' ], $subject->getAttribute( 'methods' ) );

			$subject = new RouteBlueprint( $router, $this->view_service );
			$subject->post();
			$this->assertEquals( [ 'POST' ], $subject->getAttribute( 'methods' ) );

			$subject = new RouteBlueprint( $router, $this->view_service );
			$subject->put();
			$this->assertEquals( [ 'PUT' ], $subject->getAttribute( 'methods' ) );

			$subject = new RouteBlueprint( $router, $this->view_service );
			$subject->patch();
			$this->assertEquals( [ 'PATCH' ], $subject->getAttribute( 'methods' ) );

			$subject = new RouteBlueprint( $router, $this->view_service );
			$subject->delete();
			$this->assertEquals( [ 'DELETE' ], $subject->getAttribute( 'methods' ) );

			$subject = new RouteBlueprint( $router, $this->view_service );
			$subject->options();
			$this->assertEquals( [ 'OPTIONS' ], $subject->getAttribute( 'methods' ) );

			$subject = new RouteBlueprint( $router, $this->view_service );
			$subject->any();
			$this->assertEquals( [
				'GET',
				'HEAD',
				'POST',
				'PUT',
				'PATCH',
				'DELETE',
				'OPTIONS',
			], $subject->getAttribute( 'methods' ) );
		}

	}
