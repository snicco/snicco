<?php


	namespace WPEmergeTests\View;

	use Codeception\TestCase\WPTestCase;
	use Mockery;
	use WPEmerge\View\PhpView;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\Contracts\ViewInterface;

	/**
	 * @coversDefaultClass \WPEmerge\View\PhpView
	 */
	class PhpViewTest extends WPTestCase {

		public function setUp() : void {

			parent::setUp();

			$this->engine  = Mockery::mock( PhpViewEngine::class )->shouldIgnoreMissing();
			$this->subject = new PhpView( $this->engine );
		}

		public function tearDown() :void  {

			parent::tearDown();
			Mockery::close();

			unset( $this->engine );
			unset( $this->subject );
		}

		/**
		 * @covers ::getFilepath
		 * @covers ::setFilepath
		 */
		public function testGetFilepath() {

			$expected = 'foo';
			$this->subject->setFilepath( $expected );
			$this->assertEquals( $expected, $this->subject->getFilepath() );
		}

		/**
		 * @covers ::getLayout
		 * @covers ::setLayout
		 */
		public function testGetLayout() {

			$expected = Mockery::mock( ViewInterface::class );
			$this->subject->setLayout( $expected );
			$this->assertSame( $expected, $this->subject->getLayout() );
		}

		/**
		 * @covers ::toString
		 */
		public function testToString_Layout() {

			$layout   = Mockery::mock( PhpView::class );
			$expected = 'foo';

			$layout->shouldReceive( 'toString' )
			       ->andReturn( $expected );

			$this->subject
				->setName( 'foo' )
				->setFilepath( 'foo' )
				->setLayout( $layout );

			$this->assertEquals( $expected, $this->subject->toString() );
		}

		/**
		 * @covers ::toString
		 */
		public function testToString_NoLayout() {

			$expected = 'foo';

			$this->engine->shouldReceive( 'getLayoutContent' )
			             ->andReturn( $expected );

			$this->subject
				->setName( 'foo' )
				->setFilepath( 'foo' );

			$this->assertEquals( $expected, $this->subject->toString() );
		}

		/**
		 * @covers ::toString
		 */
		public function testToString_WithoutName() {

			$this->expectExceptionMessage('must have a name');

			$this->subject->toString();
		}

		/**
		 * @covers ::toString
		 */
		public function testToString_WithoutFilepath() {

			$this->expectExceptionMessage('must have a filepath');

			$this->subject->setName( 'foo' );
			$this->subject->toString();
		}

		/**
		 * @covers ::toResponse
		 */
		public function testToResponse() {

			$expected = 'foobar';

			$mock = Mockery::mock( PhpView::class )->makePartial();
			$mock->shouldReceive( 'toString' )
			     ->andReturn( $expected );

			$result = $mock->toResponse();
			$this->assertEquals( 'text/html', $result->getHeaderLine( 'Content-Type' ) );
			$this->assertEquals( $expected, $result->getBody()->read( strlen( $expected ) ) );
		}

	}
