<?php

declare(strict_types=1);

namespace Tests\unit\View;

use Snicco\View\PhpViewFinder;
use PHPUnit\Framework\TestCase;

class PhpViewFinderTest extends TestCase
{
    
    private PhpViewFinder $finder;
    
    public function setUp() :void
    {
        
        parent::setUp();
        
        $this->finder = new PhpViewFinder([VIEWS_DIR]);
        
    }
    
    /** @test */
    public function file_existence_can_be_checked()
    {
        
        $this->assertTrue($this->finder->exists(VIEWS_DIR.DS.'view.php'));
        $this->assertTrue($this->finder->exists('view.php'));
        $this->assertTrue($this->finder->exists('view'));
        $this->assertFalse($this->finder->exists('nonexistent'));
        $this->assertFalse($this->finder->exists(''));
        
    }
    
    /** @test */
    public function file_existence_can_be_checked_if_we_are_in_a_subdirectory()
    {
        
        $this->assertTrue($this->finder->exists('subdirectory.subview.php'));
        $this->assertFalse($this->finder->exists('subdirectory.bogus.php'));
        
    }
    
    /** @test */
    public function nested_files_can_be_found_by_dot_notation()
    {
        
        // only direct child files
        $finder = new PhpViewFinder([VIEWS_DIR]);
        $this->assertTrue($finder->exists('view.php'));
        $this->assertFalse($finder->exists('first.php'));
        
        // one child dir
        $finder = new PhpViewFinder([VIEWS_DIR]);
        $this->assertTrue($finder->exists('view'));
        $this->assertTrue($finder->exists('level-one.first'));
        $this->assertTrue($finder->exists('level-one.level-two.second'));
        
        $this->assertFalse($finder->exists('level-one.level-two.third'));
        
        $this->assertTrue($finder->exists('level-one.level-two.level-three.third'));
        
    }
    
    /** @test */
    public function file_paths_can_be_retrieved()
    {
        
        $expected = VIEWS_DIR.DS.'view.php';
        
        $this->assertEquals($expected, $this->finder->filePath($expected));
        $this->assertEquals($expected, $this->finder->filePath('view.php'));
        $this->assertEquals($expected, $this->finder->filePath('view'));
        $this->assertEquals('', $this->finder->filePath('nonexistent'));
        $this->assertEquals('', $this->finder->filePath(''));
    }
    
    /** @test */
    public function absolute_file_paths_can_be_retrieved()
    {
        
        $directory = VIEWS_DIR;
        $file = $directory.DS.'view.php';
        
        $this->assertEquals($file, $this->finder->filePath($file));
        $this->assertEquals('', $this->finder->filePath($directory));
        $this->assertEquals('', $this->finder->filePath('nonexistent'));
        $this->assertEquals('', $this->finder->filePath(''));
    }
    
    /** @test */
    public function files_can_be_retrieved_from_custom_directories()
    {
        
        $subdirectory = VIEWS_DIR.DS.'subdirectory';
        $view = VIEWS_DIR.DS.'view.php';
        $subview = $subdirectory.DS.'subview.php';
        
        $finder = new PhpViewFinder([VIEWS_DIR]);
        $this->assertEquals($view, $finder->filePath('/view.php'));
        $this->assertEquals($view, $finder->filePath('view.php'));
        $this->assertEquals($view, $finder->filePath('/view'));
        $this->assertEquals($view, $finder->filePath('view'));
        $this->assertEquals('', $finder->filePath('/nonexistent'));
        $this->assertEquals('', $finder->filePath('nonexistent'));
        
        $finder = new PhpViewFinder([VIEWS_DIR, $subdirectory]);
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