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

/**
 * @internal
 */
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
                'kernel' => [
                    'bootstrappers' => [Bootstrap1::class],
                    'bundles' => [],
                ],
            ])
        );

        $app->boot();

        $this->assertTrue($app->container()[Bootstrap1::class]->registered);
        $this->assertTrue($app->container()[Bootstrap1::class]->booted);
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

        $this->assertTrue($app->container()->make(BundleInfo::class)->registered);
        $this->assertTrue($app->container()->make(BundleInfo::class)->booted);

        $this->assertTrue($app->container()->make(Bootstrap2::class)->registered);
        $this->assertTrue($app->container()->make(Bootstrap2::class)->booted);
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
                'kernel' => [
                    'bootstrappers' => [BootstrapperWithExceptionInBoostrap::class],
                    'bundles' => [],
                ],
            ])
        );

        $this->expectException(ContainerIsLocked::class);
        $this->expectExceptionMessage('id [stdClass]');
        $app->boot();
    }
}

final class BundleInfo implements Bundle
{
    public bool $registered = false;

    public bool $booted = false;

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
        $b = new self();
        $b->registered = true;
        $kernel->container()[BundleInfo::class] = $b;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $kernel->container()
            ->make(BundleInfo::class)->booted = true;
    }

    public function alias(): string
    {
        return 'bundle_info';
    }
}

final class Bootstrap1 implements Bootstrapper
{
    public bool $registered = false;

    public bool $booted = false;

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->set('bootstrapper1_configured', true);
    }

    public function register(Kernel $kernel): void
    {
        $instance = new self();
        $instance->registered = true;
        $c = $kernel->container();
        $c->instance(Bootstrap1::class, $instance);
    }

    public function bootstrap(Kernel $kernel): void
    {
        $container = $kernel->container();
        $container->make(Bootstrap1::class)->booted = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }
}

final class Bootstrap2 implements Bootstrapper
{
    public bool $registered = false;

    public bool $booted = false;

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $kernel->afterConfiguration(function (WritableConfig $config) {
            $config->set('abc', 'def');
        });
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        if (! $kernel->container()->has(BundleInfo::class)) {
            throw new RuntimeException('Bootstrapper registered before bundle');
        }

        $instance = new self();
        $instance->registered = true;

        $container[self::class] = $instance;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $container = $kernel->container();
        if (! $container->make(BundleInfo::class)->booted) {
            throw new RuntimeException('Bootstrapper bootstrapped before bundle');
        }

        $container->make(self::class)->booted = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }
}

final class BootstrapperWithExceptionInBoostrap implements Bootstrapper
{
    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
    }

    public function bootstrap(Kernel $kernel): void
    {
        $kernel->container()[stdClass::class] = new stdClass();
    }
}
