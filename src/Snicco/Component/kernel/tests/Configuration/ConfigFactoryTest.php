<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\Configuration;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Kernel\Configuration\ConfigFactory;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\Tests\helpers\CreateTestContainer;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Kernel\ValueObject\PHPCacheFile;
use Symfony\Component\Finder\Finder;

use function file_put_contents;
use function var_export;

use const DIRECTORY_SEPARATOR;

final class ConfigFactoryTest extends TestCase
{

    use CreateTestContainer;

    private string $base_dir;
    private string $cache_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = dirname(__DIR__) . '/fixtures';
        $this->cache_dir = $this->base_dir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache';
        $this->cleanDirs();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanDirs();
    }

    /**
     * @test
     */
    public function a_configuration_instance_can_be_created(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $config = (new ConfigFactory())->load($app->directories()->configDir());

        $this->assertArrayHasKey('app', $config);
        $this->assertArrayHasKey('custom-config', $config);
    }

    /**
     * @test
     */
    public function all_files_in_the_config_directory_are_loaded_and_included_as_a_root_node_in_the_config(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $config = (new ConfigFactory())->load($app->directories()->configDir());

        $this->assertSame('bar', $config['app']['foo']);
        $this->assertSame('baz', $config['custom-config']['foo']);
    }

    /**
     * @test
     */
    public function the_configuration_can_be_cached(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $file = new PHPCacheFile($app->directories()->cacheDir(), 'prod.config.php');

        $this->assertFalse($file->isCreated());

        $factory = new ConfigFactory();

        $config = [
            'app' => [
                'foo' => 'bar',
            ],
        ];

        $factory->writeToCache($file->realPath(), $config);

        $this->assertTrue(
            $file->isCreated(),
            'Config cache not created.'
        );

        $loaded = $factory->load($app->directories()->configDir(), $file);
        $this->assertEquals($config, $loaded);
    }

    /**
     * @test
     */
    public function the_configuration_can_be_read_from_cache(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $arr = [
            'app' => [
                'foo' => 'baz',
            ],
        ];

        $success = file_put_contents(
            $this->cache_dir . DIRECTORY_SEPARATOR . 'prod.config.php',
            '<?php return ' . var_export($arr, true) . ';'
        );

        if (false === $success) {
            throw new RuntimeException('cache not created in test setup');
        }

        $config = (new ConfigFactory())->load(
            $app->directories()->configDir(),
            new PHPCacheFile($app->directories()->cacheDir(), 'prod.config.php')
        );

        $this->assertIsArray($config);
        $this->assertSame('baz', $config['app']['foo']);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_the_cached_config_does_not_contain_the_app_key(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $arr = [
            'wrong' => [
                'foo' => 'baz',
            ],
        ];

        $success = file_put_contents(
            $file = $this->cache_dir . DIRECTORY_SEPARATOR . 'prod.config.php',
            '<?php return ' . var_export($arr, true) . ';'
        );

        if (false === $success) {
            throw new RuntimeException('cache not created in test setup');
        }

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(
            "The [app] key is not present in the cached config.\nUsed cache file [$file]."
        );

        (new ConfigFactory())->load(
            $app->directories()->configDir(),
            new PHPCacheFile($app->directories()->cacheDir(), 'prod.config.php')
        );
    }

    /**
     * @test
     */
    public function test_exception_if_config_file_does_not_return_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reading the [invalid] config did not return an array');

        (new ConfigFactory())->load(
            $this->base_dir . '/config_no_array_return',
        );
    }

    /**
     * @test
     */
    public function test_exception_if_cached_config_does_not_return_array(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $arr = 'foo';

        $success = file_put_contents(
            $file = $this->cache_dir . DIRECTORY_SEPARATOR . 'prod.config.php',
            '<?php return ' . var_export($arr, true) . ';'
        );

        if (false === $success) {
            throw new RuntimeException('cache not created in test setup');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "The cached config did not return an array.\nUsed cache file [$file]."
        );

        (new ConfigFactory())->load(
            $app->directories()->configDir(),
            new PHPCacheFile($app->directories()->cacheDir(), 'prod.config.php')
        );
    }

    protected function cleanDirs(): void
    {
        $files = Finder::create()->in($this->cache_dir);
        foreach ($files as $file) {
            @unlink($file->getRealPath());
        }
    }

}