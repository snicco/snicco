<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\Utils;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Kernel\ValueObject\CacheFile;

final class CacheFileTest extends TestCase
{

    private string $file;

    /**
     * @test
     */
    public function test_exception_if_dir_does_not_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CacheFile(__DIR__ . '/foo', 'foo.php');
    }

    /**
     * @test
     */
    public function test_exception_for_empty_file_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The cache file name can not be empty.');
        new CacheFile(__DIR__, '');
    }

    /**
     * @test
     */
    public function test_is_created(): void
    {
        $cache_file = new CacheFile(__DIR__, 'foo.php');
        $this->assertFalse($cache_file->isCreated());

        touch($this->file);

        $this->assertTrue($cache_file->isCreated());
    }

    /**
     * @test
     */
    public function test_as_string(): void
    {
        $cache_file = new CacheFile(__DIR__, 'foo.php');
        $this->assertSame($this->file, $cache_file->realpath());
    }

    /**
     * @test
     */
    public function test_get_contents(): void
    {
        file_put_contents($this->file, '<?php return "foo";');

        $cache = new CacheFile(__DIR__, 'foo.php');

        $content = $cache->getContents();
        $this->assertSame('<?php return "foo";', $content);
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