<?php


	namespace Tests\wpunit\Application;

	use BadMethodCallException;
	use Codeception\TestCase\WPTestCase;
	use Mockery;
	use WPEmerge\Application\ApplicationTrait;
	use WPEmerge\Exceptions\ConfigurationException;

	/**
	 * @coversDefaultClass \WPEmerge\Application\ApplicationTrait
	 */
	class ApplicationTraitTest extends WPTestCase {


		protected function setUp() : void {


			parent::setUp();

		}

		public function tearDown() : void {

			Mockery::close();

			FooApp::setApplication( null );
			BarApp::setApplication( null );

			parent::tearDown();

		}

		/**
		 * @covers ::make
		 */
		public function testMake_NullInstance_NewInstance() {

			$this->assertNull( FooApp::getApplication() );
			$app = FooApp::make();
			$this->assertSame( $app, FooApp::getApplication() );
		}

		/**
		 * @covers ::make
		 */
		public function testMake_OldInstance_NewInstance() {

			$old = FooApp::make();
			$this->assertSame( $old, FooApp::getApplication() );
			$new = FooApp::make();
			$this->assertSame( $new, FooApp::getApplication() );
		}

		/**
		 * @covers ::make
		 */
		public function testMake_MultipleApps_DifferentInstances() {

			$this->assertNull( FooApp::getApplication() );
			$this->assertNull( BarApp::getApplication() );

			$foo = FooApp::make();

			$this->assertSame( $foo, FooApp::getApplication() );
			$this->assertNull( BarApp::getApplication() );

			$bar = BarApp::make();

			$this->assertSame( $foo, FooApp::getApplication() );
			$this->assertSame( $bar, BarApp::getApplication() );
			$this->assertNotSame( FooApp::getApplication(), BarApp::getApplication() );
		}

		/**
		 * @covers ::__callStatic
		 */
		public function testCallStatic_NullInstance_Exception() {

			$this->expectExceptionMessage( 'Application instance not created' );
			$this->expectException( ConfigurationException::class );

			FooApp::foo();
		}

		/**
		 * @covers ::__callStatic
		 */
		public function testCallStatic_InvalidMethod_Exception() {

			$this->expectExceptionMessage( 'does not exist' );
			$this->expectException( BadMethodCallException::class );

			FooApp::make();
			FooApp::traitTestMagicMethod();
		}

		/**
		 * @covers ::__callStatic
		 */
		public function testCallStatic_Method_MethodCalled() {

			FooApp::make();
			FooApp::alias( 'traitTestMagicMethod', function () {

				return 'foo';
			} );

			$this->assertTrue( FooApp::hasAlias( 'traitTestMagicMethod' ) );
		}

		/**
		 * @covers ::__callStatic
		 */
		public function testCallStatic_MagicMethod_MethodCalled() {

			FooApp::make();
			FooApp::alias( 'traitTestMagicMethod', function () {

				return 'foo';
			} );

			$this->assertSame( 'foo', FooApp::traitTestMagicMethod() );
		}

	}


	class FooApp {

		use ApplicationTrait;
	}


	class BarApp {

		use ApplicationTrait;
	}
