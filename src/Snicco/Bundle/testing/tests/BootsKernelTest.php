<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Testing\BootsKernel;
use Snicco\Component\Kernel\Bootstrapper;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function is_dir;
use function is_file;

final class BootsKernelTest extends TestCase
{
    use BootsKernel;

    /**
     * @var array<class-string<Bootstrapper>>
     */
    private array $bootstrappers = [];

    /**
     * @var array<'testing'|'prod'|'dev'|'staging'|'all', list<class-string<Bundle>>>
     */
    private array $bundles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootstrappers = [
            TestingBundleBootstrapper1::class,
            TestingBundleBootstrapper2::class
        ];
        $this->bundles = [
            Environment::ALL => [
                TestingBundleBundle1::class
            ]
        ];
    }

    /**
     * @test
     */
    public function test_setUpDirectories(): void
    {
        $base_directory = $this->setUpDirectories(__DIR__ . '/fixtures/tmp');

        $kernel = new Kernel($this->container(), Environment::testing(), $base_directory);
        $this->assertInstanceOf(Kernel::class, $kernel);

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_tearDownDirectories(): void
    {
        $dir = __DIR__ . '/fixtures/tmp';

        $base_directory = $this->setUpDirectories($dir);

        $kernel = new Kernel($this->container(), Environment::testing(), $base_directory);
        $this->assertInstanceOf(Kernel::class, $kernel);

        $kernel->boot();

        $this->assertTrue(is_dir($dir));
        $this->assertTrue(is_dir($dir . '/var/cache'));
        $this->assertTrue(is_dir($dir . '/config'));
        $this->assertTrue(is_dir($dir . '/var/log'));
        $this->assertTrue(is_file($dir . '/config/app.php'));

        $this->tearDownDirectories($dir);

        $this->assertFalse(is_dir($dir));
        $this->assertFalse(is_dir($dir . '/var/cache'));
        $this->assertFalse(is_dir($dir . '/config'));
        $this->assertFalse(is_dir($dir . '/var/log'));
        $this->assertFalse(is_file($dir . '/config/app.php'));
    }

    /**
     * @test
     */
    public function test_withFixedConfig(): void
    {
        $base_directory = $this->setUpDirectories(__DIR__ . '/fixtures/tmp');

        $kernel = $this->bootWithFixedConfig([
            'foo' => [
                'bar'
            ],
            'baz' => [
                'biz'
            ],
            'app' => [
                'bootstrappers' => [
                ]
            ],
            'bundles' => [
                TestingBundleBundle1::class
            ]
        ], $base_directory);

        $this->assertSame([
            'foo' => [
                'bar'
            ],
            'baz' => [
                'biz'
            ],
            'app' => [
                'bootstrappers' => [
                ]
            ],
            'bundles' => [
                TestingBundleBundle1::class
            ]
        ], $kernel->config()->toArray());
        $this->assertEquals(Environment::testing(), $kernel->env());
    }

    /**
     * @test
     */
    public function test_bootstrappers_and_bundles_are_automatically_merged_if_not_provided_explicitly(): void
    {
        $base_directory = $this->setUpDirectories(__DIR__ . '/fixtures/tmp');

        $kernel = $this->bootWithFixedConfig([
            'foo' => [
                'bar'
            ],
            'baz' => [
                'biz'
            ],
            'app' => [
                'name' => 'my_app'
            ],
        ], $base_directory, Environment::prod());

        $this->assertSame([
            'foo' => [
                'bar'
            ],
            'baz' => [
                'biz'
            ],
            'app' => [
                'name' => 'my_app',
                'bootstrappers' => [
                    TestingBundleBootstrapper1::class,
                    TestingBundleBootstrapper2::class
                ]
            ],
            'bundles' => [
                Environment::ALL => [TestingBundleBundle1::class]
            ]
        ], $kernel->config()->toArray());
        $this->assertEquals(Environment::prod(), $kernel->env());
    }

    /**
     * @test
     */
    public function test_assertCanBeResolved_can_pass(): void
    {
        $base_directory = $this->setUpDirectories(__DIR__ . '/fixtures/tmp');

        $kernel = $this->bootWithFixedConfig([
            'bundles' => [
                Environment::ALL => [TestingBundleBundle1::class]
            ]
        ], $base_directory);

        $this->assertCanBeResolved(ServiceA::class, $kernel);
    }

    /**
     * @test
     */
    public function test_assertCanBeResolved_can_fail(): void
    {
        $base_directory = $this->setUpDirectories(__DIR__ . '/fixtures/tmp');

        $kernel = $this->bootWithFixedConfig([
            'bundles' => [
                Environment::ALL => [TestingBundleBundle1::class]
            ]
        ], $base_directory);

        try {
            $this->assertCanBeResolved(ServiceB::class, $kernel);
            $this->fail('Assertion did not fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('ServiceB', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function test_assertNotBound_can_pass(): void
    {
        $base_directory = $this->setUpDirectories(__DIR__ . '/fixtures/tmp');

        $kernel = $this->bootWithFixedConfig([
            'bundles' => [
                Environment::PROD => [TestingBundleBundle1::class]
            ]
        ], $base_directory, Environment::dev());

        $this->assertNotBound(ServiceA::class, $kernel);
        $this->assertNotBound(ServiceB::class, $kernel);
    }

    /**
     * @test
     */
    public function test_assertNotBound_can_fail(): void
    {
        $base_directory = $this->setUpDirectories(__DIR__ . '/fixtures/tmp');

        $kernel = $this->bootWithFixedConfig([
            'bundles' => [
                Environment::PROD => [TestingBundleBundle1::class]
            ]
        ], $base_directory, Environment::prod());

        try {
            $this->assertNotBound(ServiceA::class, $kernel);
            $this->fail('Assertion did not fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('was bound', $e->getMessage());
        }
    }

    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    protected function bundles(): array
    {
        return $this->bundles;
    }

}

class ServiceA
{

    private ServiceB $b;

    public function __construct(ServiceB $b)
    {
        $this->b = $b;
    }

}

class ServiceB
{

}

class TestingBundleBundle1 implements Bundle
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
        $kernel->container()->singleton(ServiceA::class, fn() => new ServiceA(new ServiceB()));
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return 'bundle1';
    }
}

class TestingBundleBootstrapper1 implements Bootstrapper
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
    }
}

class TestingBundleBootstrapper2 implements Bootstrapper
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
    }
}