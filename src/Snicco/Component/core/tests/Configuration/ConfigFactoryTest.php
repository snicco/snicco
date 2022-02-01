<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Configuration\ConfigFactory;
use Snicco\Component\Core\Directories;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Tests\helpers\CreateTestContainer;
use Snicco\Component\Core\Utils\PHPCacheFile;
use Symfony\Component\Finder\Finder;

use function file_put_contents;
use function var_export;

use const DIRECTORY_SEPARATOR;

final class ConfigFactoryTest extends TestCase
{

    use CreateTestContainer;

    private string $base_dir;
    private string $cache_dir;

    /**
     * @test
     */
    public function a_configuration_instance_can_be_created(): void
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $config = (new ConfigFactory())->load($app->directories()->configDir());

        $this->assertIsArray($config);
        $this->assertArrayHasKey('app', $config);
        $this->assertArrayHasKey('custom-config', $config);
    }

    /**
     * @test
     */
    public function all_files_in_the_config_directory_are_loaded_and_included_as_a_root_node_in_the_config(): void
    {
        $app = new Application(
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
        $app = new Application(
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
        $app = new Application(
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
        $app = new Application(
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

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            "The [app] key is not present in the cached config.\nUsed cache file [$file]."
        );

        (new ConfigFactory())->load(
            $app->directories()->configDir(),
            new PHPCacheFile($app->directories()->cacheDir(), 'prod.config.php')
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = dirname(__DIR__) . '/fixtures';
        $this->cache_dir = $this->base_dir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache';
        $this->cleanDirs();
    }

    protected function cleanDirs(): void
    {
        $files = Finder::create()->in($this->cache_dir);
        foreach ($files as $file) {
            @unlink($file->getRealPath());
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanDirs();
    }

}