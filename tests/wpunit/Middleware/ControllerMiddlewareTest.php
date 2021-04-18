<?php


	namespace Tests\wpunit\Responses;

	use Codeception\PHPUnit\TestCase;
	use Mockery;
	use WPEmerge\Middleware\ControllerMiddleware;

	/**
	 * @coversDefaultClass \WPEmerge\Middleware\ControllerMiddleware
	 */
	class ControllerMiddlewareTest extends TestCase {

		public function setUp() : void {

			parent::setUp();
		}

		public function tearDown() : void {

			parent::tearDown();
			Mockery::close();
		}

		/**
		 * @covers ::appliesTo
		 */
		public function testAppliesTo_Empty_All() {

			$subject = new ControllerMiddleware( [] );

			$this->assertTrue( $subject->appliesTo( 'foo' ) );
		}

		/**
		 * @covers ::appliesTo
		 */
		public function testAppliesTo_Only() {

			$subject = new ControllerMiddleware( [] );

			$subject->only( 'foo' );

			$this->assertTrue( $subject->appliesTo( 'foo' ) );
			$this->assertFalse( $subject->appliesTo( 'bar' ) );
		}

		/**
		 * @covers ::appliesTo
		 */
		public function testAppliesTo_Except() {

			$subject = new ControllerMiddleware( [] );

			$subject->except( 'foo' );

			$this->assertFalse( $subject->appliesTo( 'foo' ) );
			$this->assertTrue( $subject->appliesTo( 'bar' ) );
		}

		/**
		 * @covers ::appliesTo
		 */
		public function testAppliesTo_OnlyExcept() {

			$subject = new ControllerMiddleware( [] );

			$subject->only( [ 'foo', 'bar' ] )->except( [ 'bar', 'baz' ] );

			$this->assertTrue( $subject->appliesTo( 'foo' ) );
			$this->assertFalse( $subject->appliesTo( 'bar' ) );
			$this->assertFalse( $subject->appliesTo( 'baz' ) );
			$this->assertFalse( $subject->appliesTo( 'foobarbaz' ) );
		}

	}
