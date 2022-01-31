<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\Utils;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Core\Utils\PHPCacheFile;
use stdClass;

final class PHPCacheFileTest extends TestCase
{

    private string $file;

    /** @test */
    public function test_exception_if_path_is_not_php()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be [.php].');

        new PHPCacheFile(__DIR__, 'foo.js');
    }

    /** @test */
    public function test_isCreated()
    {
        $cache = new PHPCacheFile(__DIR__, 'foo.php');
        $this->assertFalse($cache->isCreated());
        touch($this->file);
        $this->assertTrue($cache->isCreated());
    }

    /** @test */
    public function test_realpath()
    {
        $cache = new PHPCacheFile(__DIR__, 'foo.php');
        $this->assertSame(__DIR__ . '/foo.php', $cache->realPath());
    }

    /** @test */
    public function test_require()
    {
        $class = new stdClass();
        $class->foo = 'bar';

        file_put_contents(
            $this->file,
            '<?php return ' . var_export($class, true) . ';'
        );

        $cache_file = new PHPCacheFile(__DIR__, 'foo.php');

        $res = $cache_file->require();

        $this->assertEquals($res, $class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = __DIR__ . '/foo.php';
        $this->assertFalse(is_file($this->file));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

}