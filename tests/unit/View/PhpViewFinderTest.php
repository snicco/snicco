<?php


	declare( strict_types = 1 );


	namespace Tests\unit\View;

	use PHPUnit\Framework\TestCase;
	use WPEmerge\View\PhpViewFinder;

	class PhpViewFinderTest extends TestCase {

		/** @var \WPEmerge\View\PhpViewFinder  */
		private $finder;

		public function setUp() : void {

			parent::setUp();

			if ( ! defined( 'WPEMERGE_TEST_DIR' ) ) {

				define( 'WPEMERGE_TEST_DIR', getenv( 'ROOT_DIR' ) . DS . 'tests' );
				define( 'TEST_VIEW_DIR', WPEMERGE_TEST_DIR . DS . 'views' . DS  );

			}
			$this->finder = new PhpViewFinder( [TEST_VIEW_DIR] );

		}


		/** @test */
		public function file_existence_can_be_checked() {

			$this->assertTrue( $this->finder->exists( TEST_VIEW_DIR . 'view.php' ) );
			$this->assertFalse( $this->finder->exists( 'nonexistent' ) );
			$this->assertFalse( $this->finder->exists( '' ) );

		}

		/** @test */
		public function file_paths_can_be_retrieved() {

			$expected = TEST_VIEW_DIR . 'view.php';

			$this->assertEquals( $expected, $this->finder->filePath( $expected ) );
			$this->assertEquals( $expected, $this->finder->filePath( 'view.php' ) );
			$this->assertEquals( $expected, $this->finder->filePath( 'view' ) );
			$this->assertEquals( '', $this->finder->filePath( 'nonexistent' ) );
			$this->assertEquals( '', $this->finder->filePath( '' ) );
		}

		/** @test */
		public function absolute_file_paths_can_be_retrieved() {

			$directory = WPEMERGE_TEST_DIR . DIRECTORY_SEPARATOR . 'views';
			$file      = $directory . DIRECTORY_SEPARATOR . 'view.php';

			$this->assertEquals( $file, $this->finder->filePath( $file ) );
			$this->assertEquals( '', $this->finder->filePath( $directory ) );
			$this->assertEquals( '', $this->finder->filePath( 'nonexistent' ) );
			$this->assertEquals( '', $this->finder->filePath( '' ) );
		}

		/** @test */
		public function files_can_be_retrieved_from_custom_directories() {

			$subdirectory = TEST_VIEW_DIR . 'subdirectory' . DS;
			$view = TEST_VIEW_DIR  . 'view.php';
			$subview = $subdirectory . 'subview.php';

			$this->finder->setDirectories( [] );
			$this->assertEquals( '', $this->finder->filePath( '/view.php' ) );
			$this->assertEquals( '', $this->finder->filePath( '/view' ) );

			$this->finder->setDirectories( [ TEST_VIEW_DIR ] );
			$this->assertEquals( $view, $this->finder->filePath( '/view.php' ) );
			$this->assertEquals( $view, $this->finder->filePath( 'view.php' ) );
			$this->assertEquals( $view, $this->finder->filePath( '/view' ) );
			$this->assertEquals( $view, $this->finder->filePath( 'view' ) );
			$this->assertEquals( '', $this->finder->filePath( '/nonexistent' ) );
			$this->assertEquals( '', $this->finder->filePath( 'nonexistent' ) );

			$this->finder->setDirectories( [ TEST_VIEW_DIR ] );
			$this->assertEquals( $view, $this->finder->filePath( '/view.php' ) );
			$this->assertEquals( $view, $this->finder->filePath( 'view.php' ) );
			$this->assertEquals( $view, $this->finder->filePath( '/view' ) );
			$this->assertEquals( $view, $this->finder->filePath( 'view' ) );
			$this->assertEquals( '', $this->finder->filePath( '/nonexistent' ) );
			$this->assertEquals( '', $this->finder->filePath( 'nonexistent' ) );

			$this->finder->setDirectories( [ TEST_VIEW_DIR, $subdirectory ] );
			$this->assertEquals( $view, $this->finder->filePath( '/view.php' ) );
			$this->assertEquals( $view, $this->finder->filePath( 'view.php' ) );
			$this->assertEquals( $view, $this->finder->filePath( '/view' ) );
			$this->assertEquals( $view, $this->finder->filePath( 'view' ) );
			$this->assertEquals( $subview, $this->finder->filePath( '/subview.php' ) );
			$this->assertEquals( $subview, $this->finder->filePath( 'subview.php' ) );
			$this->assertEquals( $subview, $this->finder->filePath( '/subview' ) );
			$this->assertEquals( $subview, $this->finder->filePath( 'subview' ) );
		}

	}