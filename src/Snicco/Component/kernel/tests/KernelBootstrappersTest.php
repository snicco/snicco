<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Kernel\Bootstrapper;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Exception\ContainerIsLocked;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\Tests\helpers\CleanDirs;
use Snicco\Component\Kernel\Tests\helpers\CreateTestContainer;
use Snicco\Component\Kernel\Tests\helpers\FixedConfigCache;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use stdClass;

final class KernelBootstrappersTest extends TestCase
{

    use CreateTestContainer;
    use CleanDirs;

    private string $fixtures_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures_dir = __DIR__ . '/fixtures';
        $this->cleanDirs([$this->fixtures_dir . '/var/cache']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanDirs([$this->fixtures_dir . '/var/cache']);
        $this->cleanDirs([$this->fixtures_dir . '/bundle_and_bootstrapper/var/cache']);
    }

    /**
     * @test
     */
    public function bootstrappers_are_loaded_from_the_app_bootstrapper_key(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->fixtures_dir),
            new FixedConfigCache([
                'app' => [
                    'bootstrappers' => [
                        Bootstrap1::class,
                    ],
                ],
            ])
        );

        $app->boot();

        $this->assertTrue($app->container()['bootstrapper_1_registered']);
        $this->assertTrue($app->container()['bootstrapper_1_booted']->val);
    }

    /**
     * @test
     */
    public function bootstrappers_are_loaded_after_external_bundles(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->fixtures_dir . '/bundle_and_bootstrapper')
        );

        $app->boot();

        $this->assertTrue($app->container()->get('bundle_info_registered'));
        $this->assertTrue($app->container()->get('bundle_info_booted')->val);

        $this->assertTrue($app->container()->get('bootstrapper_2_registered'));
        $this->assertTrue($app->container()->get('bootstrapper_2_booted')->val);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_the_container_is_modified_after_the_register_method(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->fixtures_dir),
            new FixedConfigCache([
                'app' => [
                    'bootstrappers' => [
                        BootrstrapperWithExceptionInBoostrap::class,
                    ],
                ],
            ])
        );

        $this->expectException(ContainerIsLocked::class);
        $this->expectExceptionMessage('id [foo]');
        $app->boot();
    }

}

class BundleInfo implements Bundle
{

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        //
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container()['bundle_info_registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $kernel->container()['bundle_info_booted'] = $std;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $kernel->container()['bundle_info_booted']->val = true;
    }

    public function alias(): string
    {
        return 'bundle_info';
    }

}

class Bootstrap1 implements Bootstrapper
{

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->set('bootstrapper1_configured', true);
    }

    public function register(Kernel $kernel): void
    {
        $c = $kernel->container();
        $c['bootstrapper_1_registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $c['bootstrapper_1_booted'] = $std;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $container = $kernel->container();
        $container['bootstrapper_1_booted']->val = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

}

class Bootstrap2 implements Bootstrapper
{

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        //
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        if (!$kernel->container()->has('bundle_info_registered')) {
            throw new RuntimeException('Bootstrapper registered before bundle');
        }
        $container['bootstrapper_2_registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $container['bootstrapper_2_booted'] = $std;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $container = $kernel->container();
        if (!$container['bundle_info_booted']->val === true) {
            throw new RuntimeException('Bootstrapper bootstrapped before bundle');
        }
        $container['bootstrapper_2_booted']->val = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

}

class BootrstrapperWithExceptionInBoostrap implements Bootstrapper
{

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        //
    }

    public function register(Kernel $kernel): void
    {
        //
    }

    public function bootstrap(Kernel $kernel): void
    {
        $kernel->container()['foo'] = 'bar';
    }

}
