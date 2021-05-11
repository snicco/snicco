<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Application;

	use BadMethodCallException;
	use Mockery as m;
	use PHPUnit\Framework\TestCase;
	use WPEmerge\Application\ApplicationTrait;
	use WPEmerge\Exceptions\ConfigurationException;


	class ApplicationTraitTest extends TestCase {


		protected function tearDown() : void {

			m::close();

			FooApp::setApplication( null );
			BarApp::setApplication( null );

			parent::tearDown();

		}

		/** @test */
		public function a_new_app_instance_can_be_created() {

			$this->assertNull( FooApp::getApplication() );
			$app = FooApp::make();
			$this->assertSame( $app, FooApp::getApplication() );

		}

		/** @test */
		public function multiple_app_instances_can_exists_independently(  ) {

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


		/** @test */
		public function exceptions_get_thrown_when_trying_to_call_a_method_on_a_non_instantiated_application_instance() {

			$this->expectExceptionMessage( 'Application instance not created' );
			$this->expectException( ConfigurationException::class );

			FooApp::foo();
		}

		/** @test */
		public function exceptions_get_thrown_when_trying_to_call_a_non_callable_method() {

			$this->expectExceptionMessage( 'does not exist' );
			$this->expectException( BadMethodCallException::class );

			FooApp::make();
			FooApp::badMethod();

		}


		/** @test */
		public function static_method_calls_get_forwarded_to_the_application_with() {

			FooApp::make();
			FooApp::alias( 'application_method', function ( $foo , $bar, $baz) {

				return $foo . $bar.  $baz;
			} );

			$this->assertSame( 'foobarbaz' , FooApp::application_method('foo', 'bar', 'baz') );



		}




	}


	class FooApp {

		use ApplicationTrait;
	}


	class BarApp {

		use ApplicationTrait;
	}
