<?php


	namespace Tests\wpunit\View;

	use Codeception\TestCase\WPTestCase;
	use Mockery;
	use WPEmerge\Helpers\MixedType;
	use WPEmerge\View\PhpViewFilesystemFinder;

	/**
	 * @coversDefaultClass \WPEmerge\View\PhpViewFilesystemFinder
	 */
	class PhpViewFilesystemFinderTest extends WPTestCase {


		/**
		 * @var \WPEmerge\View\PhpViewFilesystemFinder;
		 */
		private $subject;

		public function setUp() : void {

			parent::setUp();

			if ( ! defined( 'WPEMERGE_TEST_DIR' ) ) {

				define( 'WPEMERGE_TEST_DIR', getenv( 'ROOT_DIR' ) . DIRECTORY_SEPARATOR . 'tests' );

			}
			$this->subject = new PhpViewFilesystemFinder( [
				get_stylesheet_directory(),
				get_template_directory(),
			] );
		}

		public function tearDown() : void {

			parent::tearDown();
			Mockery::close();

			unset( $this->subject );
		}

		/**
		 * @covers ::exists
		 */
		public function testExists() {

			$this->assertTrue( $this->subject->exists( WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'view.php' ) );
			$this->assertTrue( $this->subject->exists( 'index.php' ) );
			$this->assertTrue( $this->subject->exists( 'index' ) );
			$this->assertFalse( $this->subject->exists( 'nonexistent' ) );
			$this->assertFalse( $this->subject->exists( '' ) );

		}

		/**
		 * @covers ::filePath
		 */
		public function testCanonical() {

			$expected = realpath( MixedType::normalizePath( locate_template( 'index.php', false ) ) );

			$this->assertEquals( $expected, $this->subject->filePath( $expected ) );
			$this->assertEquals( $expected, $this->subject->filePath( 'index.php' ) );
			$this->assertEquals( $expected, $this->subject->filePath( 'index' ) );
			$this->assertEquals( '', $this->subject->filePath( 'nonexistent' ) );
			$this->assertEquals( '', $this->subject->filePath( '' ) );
		}

		/**
		 * @covers ::resolveFilepath
		 * @covers ::resolveFromAbsoluteFilepath
		 */
		public function testResolveFilepath_AbsoluteFilepath() {

			$directory = WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'views';
			$file      = $directory . DIRECTORY_SEPARATOR . 'view.php';

			$this->assertEquals( $file, $this->subject->resolveFilepath( $file ) );
			$this->assertEquals( '', $this->subject->resolveFilepath( $directory ) );
			$this->assertEquals( '', $this->subject->resolveFilepath( 'nonexistent' ) );
			$this->assertEquals( '', $this->subject->resolveFilepath( '' ) );
		}

		/**
		 * @covers ::resolveFilepath
		 * @covers ::resolveFromCustomDirectories
		 */
		public function testResolveFilepath_CustomDirectories() {

			$fixtures = WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'views';
			$subdirectory = WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'subdirectory';
			$view = $fixtures . DIRECTORY_SEPARATOR . 'view.php';
			$subview = $subdirectory . DIRECTORY_SEPARATOR . 'subview.php';

			$this->subject->setDirectories( [] );
			$this->assertEquals( '', $this->subject->resolveFilepath( '/view.php' ) );
			$this->assertEquals( '', $this->subject->resolveFilepath( '/view' ) );

			$this->subject->setDirectories( [ $fixtures ] );
			$this->assertEquals( $view, $this->subject->resolveFilepath( '/view.php' ) );
			$this->assertEquals( $view, $this->subject->resolveFilepath( 'view.php' ) );
			$this->assertEquals( $view, $this->subject->resolveFilepath( '/view' ) );
			$this->assertEquals( $view, $this->subject->resolveFilepath( 'view' ) );
			$this->assertEquals( '', $this->subject->resolveFilepath( '/nonexistent' ) );
			$this->assertEquals( '', $this->subject->resolveFilepath( 'nonexistent' ) );

			$this->subject->setDirectories( [ WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR ] );
			$this->assertEquals( $view, $this->subject->resolveFilepath( '/view.php' ) );
			$this->assertEquals( $view, $this->subject->resolveFilepath( 'view.php' ) );
			$this->assertEquals( $view, $this->subject->resolveFilepath( '/view' ) );
			$this->assertEquals( $view, $this->subject->resolveFilepath( 'view' ) );
			$this->assertEquals( '', $this->subject->resolveFilepath( '/nonexistent' ) );
			$this->assertEquals( '', $this->subject->resolveFilepath( 'nonexistent' ) );

			$this->subject->setDirectories( [ $fixtures, $subdirectory ] );
			$this->assertEquals( $view, $this->subject->resolveFilepath( '/view.php' ) );
			$this->assertEquals( $view, $this->subject->resolveFilepath( 'view.php' ) );
			$this->assertEquals( $view, $this->subject->resolveFilepath( '/view' ) );
			$this->assertEquals( $view, $this->subject->resolveFilepath( 'view' ) );
			$this->assertEquals( $subview, $this->subject->resolveFilepath( '/subview.php' ) );
			$this->assertEquals( $subview, $this->subject->resolveFilepath( 'subview.php' ) );
			$this->assertEquals( $subview, $this->subject->resolveFilepath( '/subview' ) );
			$this->assertEquals( $subview, $this->subject->resolveFilepath( 'subview' ) );
		}

	}
