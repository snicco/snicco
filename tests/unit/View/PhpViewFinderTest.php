<?php


    declare(strict_types = 1);


    namespace Tests\unit\View;

    use PHPUnit\Framework\TestCase;
    use WPEmerge\View\PhpViewFinder;

    class PhpViewFinderTest extends TestCase
    {

        /** @var PhpViewFinder */
        private $finder;

        public function setUp() : void
        {

            parent::setUp();

            if ( ! defined('WPEMERGE_TEST_DIR')) {

                define('WPEMERGE_TEST_DIR', getenv('ROOT_DIR').DS.'tests');
                define('TEST_VIEW_DIR', WPEMERGE_TEST_DIR.DS.'views'.DS);

            }
            $this->finder = new PhpViewFinder([TEST_VIEW_DIR]);

        }


        /** @test */
        public function file_existence_can_be_checked()
        {

            $this->assertTrue($this->finder->exists(TEST_VIEW_DIR.'view.php'));
            $this->assertTrue($this->finder->exists('view.php'));
            $this->assertTrue($this->finder->exists('view'));
            $this->assertFalse($this->finder->exists('nonexistent'));
            $this->assertFalse($this->finder->exists(''));

        }

        /** @test */
        public function file_existence_can_be_checked_if_we_are_in_a_subdirectory()
        {

            $this->assertTrue($this->finder->exists('subview.php'));
            $this->assertFalse($this->finder->exists('bogus.php'));

        }

        /** @test */
        public function nested_files_can_be_found_if_we_adjust_the_search_depth () {

            // only direct child files
            $finder = new PhpViewFinder([TEST_VIEW_DIR], 0);
            $this->assertTrue($finder->exists('view.php'));
            $this->assertFalse($finder->exists('first.php'));

            // one child dir
            $finder = new PhpViewFinder([TEST_VIEW_DIR], 1);
            $this->assertTrue($finder->exists('view.php'));
            $this->assertTrue($finder->exists('first.php'));
            $this->assertFalse($finder->exists('second.php'));

            // two child dirs
            $finder = new PhpViewFinder([TEST_VIEW_DIR], 2);
            $this->assertTrue($finder->exists('view.php'));
            $this->assertTrue($finder->exists('first.php'));
            $this->assertTrue($finder->exists('second.php'));
            $this->assertFalse($finder->exists('third.php'));

            // two child, but work if we give it also the subdirectory
            $finder = new PhpViewFinder([TEST_VIEW_DIR, TEST_VIEW_DIR. 'level-one' ], 2);
            $this->assertTrue($finder->exists('view.php'));
            $this->assertTrue($finder->exists('first.php'));
            $this->assertTrue($finder->exists('second.php'));
            $this->assertTrue($finder->exists('third.php'));

        }

        /** @test */
        public function file_paths_can_be_retrieved()
        {

            $expected = TEST_VIEW_DIR.'view.php';

            $this->assertEquals($expected, $this->finder->filePath($expected));
            $this->assertEquals($expected, $this->finder->filePath('view.php'));
            $this->assertEquals($expected, $this->finder->filePath('view'));
            $this->assertEquals('', $this->finder->filePath('nonexistent'));
            $this->assertEquals('', $this->finder->filePath(''));
        }

        /** @test */
        public function absolute_file_paths_can_be_retrieved()
        {

            $directory = WPEMERGE_TEST_DIR.DIRECTORY_SEPARATOR.'views';
            $file = $directory.DIRECTORY_SEPARATOR.'view.php';

            $this->assertEquals($file, $this->finder->filePath($file));
            $this->assertEquals('', $this->finder->filePath($directory));
            $this->assertEquals('', $this->finder->filePath('nonexistent'));
            $this->assertEquals('', $this->finder->filePath(''));
        }

        /** @test */
        public function files_can_be_retrieved_from_custom_directories()
        {

            $subdirectory = TEST_VIEW_DIR.'subdirectory'.DS;
            $view = TEST_VIEW_DIR.'view.php';
            $subview = $subdirectory.'subview.php';



            $finder = new PhpViewFinder([TEST_VIEW_DIR]);
            $this->assertEquals($view, $finder->filePath('/view.php'));
            $this->assertEquals($view, $finder->filePath('view.php'));
            $this->assertEquals($view, $finder->filePath('/view'));
            $this->assertEquals($view, $finder->filePath('view'));
            $this->assertEquals('', $finder->filePath('/nonexistent'));
            $this->assertEquals('', $finder->filePath('nonexistent'));


            $finder = new PhpViewFinder([TEST_VIEW_DIR, $subdirectory]);
            $this->assertEquals($view, $finder->filePath('/view.php'));
            $this->assertEquals($view, $finder->filePath('view.php'));
            $this->assertEquals($view, $finder->filePath('/view'));
            $this->assertEquals($view, $finder->filePath('view'));
            $this->assertEquals($subview, $finder->filePath('/subview.php'));
            $this->assertEquals($subview, $finder->filePath('subview.php'));
            $this->assertEquals($subview, $finder->filePath('/subview'));
            $this->assertEquals($subview, $finder->filePath('subview'));
        }

    }