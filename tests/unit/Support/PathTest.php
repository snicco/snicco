<?php

declare(strict_types=1);

namespace Tests\unit\Support;

use Snicco\Support\FilePath;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    
    /** @test */
    public function normalize_path()
    {
        
        $ds = DIRECTORY_SEPARATOR;
        $input = '/foo\\bar/baz\\\\foobar';
        
        $this->assertEquals("{$ds}foo{$ds}bar{$ds}baz{$ds}foobar", FilePath::normalize($input));
        $this->assertEquals('/foo/bar/baz/foobar', FilePath::normalize($input, '/'));
        $this->assertEquals('\\foo\\bar\\baz\\foobar', FilePath::normalize($input, '\\'));
    }
    
    /** @test */
    public function add_trailing_slash()
    {
        
        $input = '/foo';
        
        $this->assertEquals("/foo/", FilePath::addTrailingSlash($input, '/'));
    }
    
    /** @test */
    public function remove_trailing_slash()
    {
        
        $input = '/foo/';
        
        $this->assertEquals("/foo", FilePath::removeTrailingSlash($input, '/'));
    }
    
}
