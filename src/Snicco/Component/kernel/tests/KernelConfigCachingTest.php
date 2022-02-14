<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Kernel\Configuration\ConfigCache;
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
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            $dir = Directories::fromDefaults($this->fixtures_dir),
            new TestConfigCache()
        );

        $expected_path = $dir->cacheDir() . '/' . 'prod.config.php';

        $this->assertFalse(is_file($expected_path));

        $app->boot();

        $this->assertTrue(is_file($expected_path));

        $saved_config = require $expected_path;
        $this->assertSame($app->config()->toArray(), $saved_config);
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

