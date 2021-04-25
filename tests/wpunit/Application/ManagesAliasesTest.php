<?php


	namespace Tests\wpunit\Application;

	use Mockery as m;
	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Application\ManagesAliases;


	class ManagesAliasesTest extends TestCase {


		/**
		 * @var \Tests\wpunit\Application\ManagesAliasImplementation
		 */
		private $subject;

		protected function setUp() : void {

			parent::setUp();

			$this->subject = new ManagesAliasImplementation();
			$this->subject->container = new BaseContainerAdapter();

		}

		public function tearDown() : void {

			parent::tearDown();
			m::close();


		}

		/** @test */
		public function has_alias() {

			$this->assertFalse( $this->subject->hasAlias( 'foo' ) );
			$this->subject->alias( 'foo', 'bar' );
			$this->assertTrue( $this->subject->hasAlias( 'foo' ) );

		}

		/** @test */
		public function get_alias() {

			$this->assertNull( $this->subject->getAlias( 'foo' ) );
			$this->subject->alias( 'foo', 'bar', 'baz' );
			$this->assertEquals( [
				'name'   => 'foo',
				'target' => 'bar',
				'method' => 'baz',
			], $this->subject->getAlias( 'foo' ) );
		}


		/** @test */
		public function if_no_alias_is_registered_an_exception_is_thrown () {

			$this->expectExceptionMessage('Method: foo does not exist.');

			$this->subject->foo();

		}

		/**
		 * @covers ::setAlias
		 */
		public function testSetAlias_String_ResolveFromContainer() {

			$alias       = 'test';
			$service_key = 'test_service';
			$service     = new \Tests\stubs\TestService();

			$this->resolver->shouldReceive( 'resolve' )
			               ->with( $service_key )
			               ->andReturn( $service );

			$this->subject->setAlias( [
				'name'   => $alias,
				'target' => $service_key,
			] );

			$this->assertSame( $service, $this->subject->{$alias}() );
		}

		/**
		 * @covers ::setAlias
		 */
		public function testSetAlias_StringWithMethod_ResolveFromContainer() {

			$alias       = 'test';
			$service_key = 'test_service';
			$service     = new \Tests\stubs\TestService();

			$this->resolver->shouldReceive( 'resolve' )
			               ->with( $service_key )
			               ->andReturn( $service );

			$this->subject->setAlias( [
				'name'   => $alias,
				'target' => $service_key,
				'method' => 'getTest',
			] );

			$this->assertSame( 'foobar', $this->subject->{$alias}() );
		}

		/**
		 * @covers ::setAlias
		 */
		public function testSetAlias_Closure_CallClosure() {

			$expected = 'foo';
			$alias    = 'test';
			$closure  = function () use ( $expected ) {

				return $expected;
			};

			$this->subject->setAlias( [
				'name'   => $alias,
				'target' => $closure,
			] );

			$this->assertEquals( $expected, $this->subject->{$alias}() );
		}

	}


	class ManagesAliasImplementation {

		use ManagesAliases;

		/** @var \SniccoAdapter\BaseContainerAdapter */
		public $container;

		public function resolve( string $key ) {

			return $this->container->make($key);

		}


	}
