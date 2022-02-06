<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\Tests\helpers\CreateTestContainer;
use Snicco\Component\Kernel\Tests\helpers\WriteTestConfig;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function touch;

final class CachedKernelTest extends TestCase
{

    use CreateTestContainer;
    use WriteTestConfig;

    private string $base_dir;
    private string $base_dir_with_bundles;

    /**
     * @test
     */
    public function test_is_config_cached(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->assertFalse($app->isConfigurationCached());

        touch($this->base_dir . '/var/cache/testing.config.php');

        $this->assertTrue($app->isConfigurationCached());

        $app = new Kernel(
            $this->createContainer(),
            Environment::dev(),
            Directories::fromDefaults($this->base_dir)
        );
        $this->assertFalse($app->isConfigurationCached());
    }

    /**
     * @test
     */
    public function the_application_can_be_booted_with_a_cached_config(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->writeConfig($app, [
            'app' => [
                'foo' => 'bar',
            ],
            'routing' => [
                'baz' => 'biz',
            ],
        ]);

        $app->boot();

        $config = $app->config();

        $this->assertSame('bar', $config['app.foo']);
        $this->assertSame('biz', $config['routing.baz']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = __DIR__ . '/fixtures';
        $this->base_dir_with_bundles = $this->base_dir . '/base_dir_with_bundles';
        $this->cleanDirs([$this->base_dir . '/var/cache', $this->base_dir_with_bundles . '/var/cache']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanDirs([$this->base_dir . '/var/cache', $this->base_dir_with_bundles . '/var/cache']);
    }

}

