<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\Bundle;

use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;

use function is_file;
use function touch;
use function unlink;

/**
 * @internal
 */
final class ConfigFilesRollbackTest extends TestCase
{
    use BundleTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        if (is_file($file = $this->fixturesDir() . '/config/other-config.php')) {
            unlink($file);
        }

        $this->bundle_test = new BundleTest($this->fixturesDir());
    }

    protected function tearDown(): void
    {
        if (is_file($file = $this->fixturesDir() . '/config/other-config.php')) {
            unlink($file);
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function configuration_files_that_are_created_are_removed_automatically(): void
    {
        $this->directories = $this->bundle_test->setUpDirectories();

        touch($this->directories->configDir() . '/other-config.php');

        $this->assertTrue(is_file($this->directories->configDir() . '/other-config.php'));

        $this->bundle_test->tearDownDirectories();

        $this->assertFalse(is_file($this->directories->configDir() . '/other-config.php'));
        $this->assertTrue(is_file($this->directories->configDir() . '/app.php'));
        $this->assertTrue(is_file($this->directories->configDir() . '/routing.php'));
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/fixtures/config-rollback';
    }
}
