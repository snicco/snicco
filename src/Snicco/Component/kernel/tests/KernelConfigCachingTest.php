<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\Tests\helpers\CleanDirs;
use Snicco\Component\Kernel\Tests\helpers\CreateTestContainer;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use stdClass;

use function is_file;

/**
 * @internal
 */
final class KernelConfigCachingTest extends TestCase
{
    use CreateTestContainer;
    use CleanDirs;

    private string $fixtures_dir;

    private string $base_dir_with_bundles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures_dir = __DIR__ . '/fixtures';
        $this->base_dir_with_bundles = $this->fixtures_dir . '/base_dir_with_bundles';
        $this->cleanDirs([$this->fixtures_dir . '/var/cache', $this->base_dir_with_bundles . '/var/cache']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanDirs([$this->fixtures_dir . '/var/cache', $this->base_dir_with_bundles . '/var/cache']);
    }

    /**
     * @test
     */
    public function the_configuration_will_be_written_to_the_cache_if_its_not_yet_created(): void
    {
        $kernel = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            $dir = Directories::fromDefaults($this->fixtures_dir),
        );

        $expected_path = $dir->cacheDir() . '/kernel.config.php';

        $this->assertFalse(is_file($expected_path));

        $kernel->boot();

        $this->assertTrue(is_file($expected_path));

        $saved_config = require $expected_path;
        $this->assertSame($kernel->config()->toArray(), $saved_config);
    }

    /**
     * @test
     */
    public function not_passing_a_cache_instance_will_default_to_null_cache_in_non_production(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::dev(),
            $dir = Directories::fromDefaults($this->fixtures_dir),
        );

        $expected_path = $dir->cacheDir() . '/' . 'prod.config.php';

        $this->assertFalse(is_file($expected_path));

        $app->boot();

        $this->assertFalse(is_file($expected_path));
    }

    /**
     * @test
     */
    public function after_register_callbacks_are_run_if_the_config_is_cached(): void
    {
        $get_kernel = function (): Kernel {
            static $run = false;
            $kernel = new Kernel(
                $this->createContainer(),
                Environment::prod(),
                Directories::fromDefaults($this->fixtures_dir),
            );
            $kernel->afterConfigurationLoaded(function (WritableConfig $config) use (&$run): void {
                if ($run) {
                    throw new RuntimeException('after configuration callback run for cached kernel');
                }

                $run = true;

                $config->set('foo_config', 'bar');
            });
            $kernel->afterRegister(function (Kernel $kernel): void {
                $kernel->container()
                    ->instance(stdClass::class, new stdClass());
            });

            return $kernel;
        };

        $expected_path = Directories::fromDefaults($this->fixtures_dir)->cacheDir() . '/kernel.config.php';
        $this->assertFalse(is_file($expected_path));

        $kernel = $get_kernel();
        $kernel->boot();

        $this->assertTrue(is_file($expected_path));

        $cached_kernel = $get_kernel();

        $cached_kernel->boot();

        $this->assertEquals(new stdClass(), $cached_kernel->container()->get(stdClass::class));
        $this->assertSame('bar', $cached_kernel->config()->get('foo_config'));
    }
}
