<?php

declare(strict_types=1);

namespace Tests\Core\unit\Support;

use InvalidArgumentException;
use Snicco\Core\Support\CacheFile;
use Tests\Codeception\shared\UnitTest;

final class CacheFileTest extends UnitTest
{
    
    private string $file;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->file = __DIR__.'/foo.php';
        $this->assertFalse(is_file($this->file));
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }
    
    /** @test */
    public function test_exception_if_dir_does_not_exist()
    {
        $this->expectException(InvalidArgumentException::class);
        $cache_file = new CacheFile(__DIR__.'/foo', 'foo.php');
    }
    
    /** @test */
    public function test_exception_for_empty_file_name()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The cache file name can not be empty.');
        $cache_file = new CacheFile(__DIR__, '');
    }
    
    /** @test */
    public function test_is_created()
    {
        $cache_file = new CacheFile(__DIR__, 'foo.php');
        $this->assertFalse($cache_file->isCreated());
        
        touch($this->file);
        
        $this->assertTrue($cache_file->isCreated());
    }
    
    /** @test */
    public function test_as_string()
    {
        $cache_file = new CacheFile(__DIR__, 'foo.php');
        $this->assertSame($this->file, $cache_file->asString());
    }
    
}