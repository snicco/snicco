<?php


	namespace WPEmergeTests\wpunit\Helpers;

	use Mockery;
	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Application\GenericFactory;
	use WPEmerge\Exceptions\ClassNotFoundException;
	use WPEmerge\Helpers\Handler;
	use stdClass;
	use WPEmergeTestTools\TestService;

	/**
	 * @coversDefaultClass \WPEmerge\Helpers\Handler
	 */
	class HandlerTest extends TestCase {

		/**
		 * @var Mockery\MockInterface|GenericFactory
		 */
		private $factory;

		public function setUp() : void {

			parent::setUp();

			$this->factory = Mockery::mock( GenericFactory::class );

			$this->factory->shouldReceive( 'make' )
			              ->andReturnUsing( function ( $class ) {

				              if ( ! class_exists( $class ) ) {
					              throw new ClassNotFoundException();
				              }

				              return new $class();
			              } );
		}

		public function tearDown() : void {

			parent::tearDown();
			Mockery::close();

			unset( $this->factory );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 */
		public function a_closure_handler_always_holds_a_closure_callable() {

			$expected = function () {
			};

			$subject = new Handler( $this->factory, $expected );

			$this->assertEquals( $expected, $subject->get() );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromArray
		 */
		public function exceptions_get_raised_for_empty_array_handlers() {

			$this->expectExceptionMessage('No or invalid handler');

			new Handler( $this->factory, [] );

		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromArray
		 */
		public function exceptions_get_raised_for_malformed_arrays() {

			$this->expectExceptionMessage('No or invalid handler');

			new Handler( $this->factory, [ '', TestService::class, 'foo' ] );

		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromArray
		 */
		public function exceptions_get_raised_for_array_handlers_without_method() {

			$this->expectExceptionMessage('No or invalid handler');

			new Handler( $this->factory, [ TestService::class ] );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromArray
		 */
		public function arrays_handlers_with_leading_backslashes_get_parsed_correctly() {

			$expected = [
				'class'     => 'WPEmergeTestTools\\TestService',
				'method'    => 'foo',
				'namespace' => '',
			];

			$subject = new Handler( $this->factory, [ '\\WPEmergeTestTools\\TestService', 'foo' ] );

			$this->assertEquals( $expected, $subject->get() );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromArray
		 */
		public function an_array_handler_without_a_default_method_works_if_its_specified_separately() {

			$expected = [
				'class'     => TestService::class,
				'method'    => 'defaultMethod',
				'namespace' => '',
			];

			$subject = new Handler( $this->factory, [ TestService::class ], 'defaultMethod' );

			$this->assertEquals( $expected, $subject->get() );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromArray
		 */
		public function exceptions_for_array_handler_with_empty_method() {

			$this->expectExceptionMessage('No or invalid handler');

			new Handler( $this->factory, [ TestService::class, '' ] );

		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromArray
		 */
		public function an_array_callable_gets_parsed_correctly() {

			$expected = [
				'class'     => TestService::class,
				'method'    => 'foo',
				'namespace' => '',
			];

			$subject = new Handler( $this->factory, [
				TestService::class,
				'foo',
			] );

			$this->assertEquals( $expected, $subject->get() );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromArray
		 */
		public function a_array_callable_with_passed_namespace_parses_the_full_path() {

			$expected = [
				'class'     => 'TestService',
				'method'    => 'defaultMethod',
				'namespace' => 'WPEmergeTestTools\\',
			];

			$subject = new Handler( $this->factory, [ 'TestService' ], 'defaultMethod', 'WPEmergeTestTools\\', ['WPEmergeTestTools'] );

			$this->assertEquals( $expected, $subject->get() );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromArray
		 */
		public function parsing_a_namespaced_class_in_the_array_works() {

			$expected = [
				'class'     => 'TestService',
				'method'    => 'defaultMethod',
				'namespace' => 'WPEmergeTestTools\\',
			];

			$subject = new Handler( $this->factory, [ \TestService::class ], 'defaultMethod', 'WPEmergeTestTools\\', ['WPEmergeTestTools'] );

			$this->assertEquals( $expected, $subject->get() );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromString
		 */
		public function parsing_from_a_string_without_default_method_works_if_specified_separately() {

			$expected = [
				'class'     => 'WPEmergeTestTools\\TestService',
				'method'    => 'defaultMethod',
				'namespace' => '',
			];

			$subject = new Handler( $this->factory, 'WPEmergeTestTools\\TestService', 'defaultMethod' );

			$this->assertEquals( $expected, $subject->get() );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromString
		 */
		public function parsing_from_a_string_without_default_does_not_work() {

			$this->expectExceptionMessage('No or invalid handler');

			new Handler( $this->factory, 'WPEmergeTestTools\\TestService' );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromString
		 */
		public function it_works_with_at_signs_to_specify_the_handler_method() {

			$expected = [
				'class'     => 'WPEmergeTestTools\\TestService',
				'method'    => 'getTest',
				'namespace' => '',
			];

			$subject = new Handler( $this->factory, 'WPEmergeTestTools\\TestService@getTest' );

			$this->assertEquals( $expected, $subject->get() );
		}

		/**
		 * @test
		 * @covers ::__construct
		 * @covers ::parse
		 * @covers ::parseFromString
		 */
		public function it_works_with_colons_to_specify_the_handler_method() {

			$expected = [
				'class'     => 'WPEmergeTestTools\\TestService',
				'method'    => 'getTest',
				'namespace' => '',
			];

			$subject = new Handler( $this->factory, 'WPEmergeTestTools\\TestService::getTest' );

			$this->assertEquals( $expected, $subject->get() );

		}

		/**
		 * @test
		 * @covers ::make
		 */
		public function passing_a_closure_always_returns_the_same_instance() {

			$expected = function () {
			};
			$subject  = new Handler( $this->factory, $expected );
			$this->assertSame( $expected, $subject->make() );

		}

		/**
		 * @test
		 * @covers ::make
		 */
		public function passing_the_fully_namespaced_class_works () {

			$subject = new Handler( $this->factory, 'WPEmergeTests\\wpunit\\Helpers\\HandlerTestMock@foo' );
			$this->assertInstanceOf( HandlerTestMock::class, $subject->make() );

		}

		/**
		 * @test
		 * @covers ::make
		 */
		public function passing_the_namespace_as_a_prefix_works() {

			$subject = new Handler( $this->factory, 'HandlerTestMock@foo', '', 'WPEmergeTests\\wpunit\\Helpers\\' );
			$this->assertInstanceOf( HandlerTestMock::class, $subject->make() );
		}

		/**
		 * @test
		 * @covers ::make
		 */
		public function an_exception_gets_raised_for_non_existing_namespaced_classes() {

			$this->expectExceptionMessage('Class not found');

			$subject = new Handler( $this->factory, 'HandlerTestMock@foo', '', 'WPEmergeTests\\NonexistentNamespace\\' );
			$subject->make();
		}

		/**
		 * @test
		 * @covers ::execute
		 */
		public function testExecute_Closure_CalledWithArguments() {

			$stub = new stdClass();
			$mock = Mockery::mock();
			$mock->shouldReceive( 'execute' )
			     ->with( $mock, $stub )
			     ->once();

			$closure = function ( $mock, $stub ) {

				$mock->execute( $mock, $stub );

			};

			$subject = new Handler( $this->factory, $closure );
			$subject->execute( $mock, $stub );
			$this->assertTrue( true );
		}

		/**
		 * @test
		 * @covers ::execute
		 */
		public function testExecute_ClassAtMethod_CalledWithArguments() {

			$foo      = 'foo';
			$bar      = 'bar';
			$expected = (object) [ 'value' => $foo . $bar ];

			$subject = new Handler( $this->factory, HandlerTestControllerMock::class . '@foobar' );


			$subject->setExecutable( function ($callable, $params ) {

				$container = new BaseContainerAdapter();

				return $container->call($callable, $params);

			} );


			$this->assertEquals( $expected, $subject->execute( 'foo', 'bar' ) );
		}

	}


	class HandlerTestMock {

		public function foo() {

			return 'foo';
		}

	}


	class HandlerTestControllerMock {

		public function foobar( $foo, $bar ) {

			return (object) [ 'value' => $foo . $bar ];
		}

	}
