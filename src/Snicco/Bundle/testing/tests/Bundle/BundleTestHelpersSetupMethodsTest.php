<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\Bundle;

use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\ValueObject\Directories;

use function is_dir;
use function is_file;
use function touch;

/**
 * @internal
 */
final class BundleTestHelpersSetupMethodsTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function set_up_and_tear_down_methods_work(): void
    {
        $this->assertInstanceOf(Directories::class, $this->directories);
        $this->assertSame($this->fixturesDir(), $this->directories->baseDir());
    }

    /**
     * @test
     */
    public function test_remove_files_does_not_remove_directories(): void
    {
        touch(__DIR__ . '/fixtures/dir/file.php');
        touch(__DIR__ . '/fixtures/dir/dir-nested/file-nested.php');

        $this->assertTrue(is_dir(__DIR__ . '/fixtures/dir'));
        $this->assertTrue(is_dir(__DIR__ . '/fixtures/dir/dir-nested'));
        $this->assertTrue(is_file(__DIR__ . '/fixtures/dir/file.php'));
        $this->assertTrue(is_file(__DIR__ . '/fixtures/dir/dir-nested/file-nested.php'));

        $this->bundle_test->removePHPFilesRecursive(__DIR__ . '/fixtures/dir');

        $this->assertTrue(is_dir(__DIR__ . '/fixtures/dir'));
        $this->assertTrue(is_dir(__DIR__ . '/fixtures/dir/dir-nested'));
        $this->assertFalse(is_file(__DIR__ . '/fixtures/dir/file.php'));
        $this->assertFalse(is_file(__DIR__ . '/fixtures/dir/dir-nested/file-nested.php'));
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/fixtures/tmp';
    }
}
