<?php

declare(strict_types=1);

namespace Snicco\Bundle\Blade\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Bridge\Blade\BladeViewFactory;
use Snicco\Bundle\Blade\BladeBundle;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;
use function is_dir;

/**
 * @internal
 */
final class BladeBundleTest extends WPTestCase
{
    use BundleTestHelpers;

    protected function tearDown(): void
    {
        $this->bundle_test->tearDownDirectories();
        $this->bundle_test->removeDirectoryRecursive($this->fixturesDir() . '/var');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_alias(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/blade-bundle'));
    }

    /**
     * @test
     */
    public function test_blade_view_factory_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->boot();

        $this->assertCanBeResolved(BladeViewFactory::class, $kernel);
    }

    /**
     * @test
     */
    public function the_blade_cache_dir_is_created(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $this->assertFalse(is_dir($this->directories->cacheDir() . '/blade'));

        $kernel->boot();

        $this->assertCanBeResolved(BladeViewFactory::class, $kernel);

        $this->assertTrue(is_dir($this->directories->cacheDir() . '/blade'));
    }

    /**
     * @test
     */
    public function test_exception_without_templating_bundle(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('kernel.bundles', [
                Environment::ALL => [BladeBundle::class],
            ]);
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('needs snicco/templating-bundle');

        $kernel->boot();
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}
