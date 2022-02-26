<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Kernel\Configuration\ConfigCache;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\Tests\helpers\CleanDirs;
use Snicco\Component\Kernel\Tests\helpers\CreateTestContainer;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function file_put_contents;
use function is_file;
use function var_export;

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
            new TestConfigCache()
        );

        $expected_path = $dir->cacheDir() . '/' . 'prod.config.php';

        $this->assertFalse(is_file($expected_path));

        $kernel->boot();

        $this->assertTrue(is_file($expected_path));

        $saved_config = require $expected_path;
        $this->assertSame($kernel->config()->toArray(), $saved_config);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_the_cached_config_does_not_contain_the_app_key(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->fixtures_dir . '/base_dir_without_app_config'),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The [app.php] config file was not found in the config dir'
        );

        $app->boot();
    }

    /**
     * @test
     */
    public function not_passing_a_cache_instance_will_default_to_file_cache_in_production(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            $dir = Directories::fromDefaults($this->fixtures_dir),
        );

        $expected_path = $dir->cacheDir() . '/' . 'prod.config.php';

        $this->assertFalse(is_file($expected_path));

        $app->boot();

        $this->assertTrue(is_file($expected_path), 'configuration file not written.');

        $saved_config = require $expected_path;
        $this->assertSame($app->config()->toArray(), $saved_config);
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

        $expected_path = $dir->cacheDir() . '/' . 'dev.config.php';

        $this->assertFalse(is_file($expected_path));

        $app->boot();

        $this->assertFalse(is_file($expected_path), 'configuration cache was created in dev env.');
    }

    /**
     * @test
     */
    public function afterRegister_callbacks_are_run_if_the_config_is_cached(): void
    {
        $get_kernel = function (): Kernel {
            static $run = false;
            $kernel = new Kernel(
                $this->createContainer(),
                Environment::prod(),
                Directories::fromDefaults($this->fixtures_dir),
                new TestConfigCache()
            );
            $kernel->afterConfigurationLoaded(function (WritableConfig $config) use (&$run) {
                if ($run === true) {
                    throw new RuntimeException('after configuration callback run for cached kernel');
                } else {
                    $run = true;
                }
                $config->set('foo_config', 'bar');
            });
            $kernel->afterRegister(function (Kernel $kernel) {
                $kernel->container()->primitive('foo_container', 'bar');
            });
            return $kernel;
        };


        $expected_path = Directories::fromDefaults($this->fixtures_dir)->cacheDir() . '/' . 'prod.config.php';
        $this->assertFalse(is_file($expected_path));

        $kernel = $get_kernel();
        $kernel->boot();

        $this->assertTrue(is_file($expected_path));

        $cached_kernel = $get_kernel();

        $cached_kernel->boot();

        $this->assertSame('bar', $cached_kernel->container()->get('foo_container'));
        $this->assertSame('bar', $cached_kernel->config()->get('foo_config'));
    }

}


class TestConfigCache implements ConfigCache
{
    /**
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function get(string $key, callable $loader): array
    {
        if (is_file($key)) {
            return require $key;
        }

        $data = $loader();

        file_put_contents($key, '<?php return ' . var_export($data, true) . ';');

        return $data;
    }
}

