<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Testing\BootsKernelForBundleTest;
use Snicco\Bundle\Testing\SwapServicesBootstrapper;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

interface DummyService
{

}

final class SwapServicesBootstrapperTest extends TestCase
{

    use BootsKernelForBundleTest;

    private Directories $directories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directories = $this->setUpDirectories(__DIR__ . '/fixtures/tmp');
    }

    protected function tearDown(): void
    {
        $this->tearDownDirectories(__DIR__ . '/fixtures/tmp');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function services_can_be_swapped_in_the_container(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories);

        $this->assertInstanceOf(FooService::class, $kernel->container()->make(DummyService::class));

        $bar_service = new BarService();

        $new_kernel = $this->bootWithFixedConfig([
            'app' => [
                'bootstrappers' => [
                    SwapServicesBootstrapper::class
                ]
            ],
            SwapServicesBootstrapper::class => [
                DummyService::class => $bar_service
            ]
        ], $this->directories);

        $this->assertInstanceOf(BarService::class, $new_kernel->container()->make(DummyService::class));
    }

    protected function bundles(): array
    {
        return [
            Environment::ALL => [
                BundleWithService::class
            ]
        ];
    }
}

class FooService implements DummyService
{
}

class BarService implements DummyService
{
}

class BundleWithService implements Bundle
{

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        ///
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container()->singleton(DummyService::class, fn() => new FooService());
    }

    public function bootstrap(Kernel $kernel): void
    {
        //
    }

    public function alias(): string
    {
        return 'bundle_service';
    }
}
