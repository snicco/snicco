<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Exception\MissingConfigKey;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\Tests\fixtures\bundles\Bundle1;
use Snicco\Component\Kernel\Tests\fixtures\bundles\Bundle2;
use Snicco\Component\Kernel\Tests\fixtures\bundles\BundleAssertsMethodOrder;
use Snicco\Component\Kernel\Tests\helpers\CleanDirs;
use Snicco\Component\Kernel\Tests\helpers\CreateTestContainer;
use Snicco\Component\Kernel\Tests\helpers\FixedBootstrapCache;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use stdClass;
use Throwable;

/**
 * @internal
 */
final class KernelBundlesTest extends TestCase
{
    use CreateTestContainer;
    use CleanDirs;

    private string $base_dir;

    private string $base_dir_with_bundles;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['_test'] = [];
        $this->base_dir = __DIR__ . '/fixtures';
        $this->base_dir_with_bundles = $this->base_dir . '/base_dir_with_bundles';
        $this->cleanDirs([$this->base_dir . '/var/cache', $this->base_dir_with_bundles . '/var/cache']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['_test']);
        $this->cleanDirs([$this->base_dir . '/var/cache', $this->base_dir_with_bundles . '/var/cache']);
    }

    /**
     * @test
     */
    public function bundles_are_loaded_from_a_bundle_php_file(): void
    {
        $app1 = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
        );

        $app1->boot();
        $this->assertFalse($app1->usesBundle('bundle_prod'));

        $app2 = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir_with_bundles)
        );

        $app2->boot();

        $this->assertTrue($app2->usesBundle('bundle_prod'));
    }

    /**
     * @test
     */
    public function the_declared_bundle_environment_has_to_match_the_current_app_env(): void
    {
        $app1 = new Kernel(
            $this->createContainer(),
            Environment::dev(),
            Directories::fromDefaults($this->base_dir_with_bundles)
        );

        $app1->boot();
        // Bundle is not present because it's declared with env => prod
        $this->assertFalse($app1->usesBundle('bundle_prod'));

        $app2 = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir_with_bundles)
        );

        $app2->boot();
        // Bundle is now present because the environment matches.
        $this->assertTrue($app2->usesBundle('bundle_prod'));
    }

    /**
     * @test
     */
    public function a_bundle_can_run_in_all_environments(): void
    {
        $app1 = new Kernel(
            $this->createContainer(),
            Environment::dev(),
            Directories::fromDefaults($this->base_dir_with_bundles),
        );

        $app1->boot();
        // Bundle is not present because it's declared with env => prod
        $this->assertFalse($app1->usesBundle('bundle_prod'));
        $this->assertTrue($app1->usesBundle('bundle_always'));
    }

    /**
     * @test
     */
    public function test_exception_if_bundle_has_wrong_interface(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
            new FixedBootstrapCache([
                'kernel' => [
                    'bundles' => [
                        Environment::PROD => [stdClass::class],
                    ],
                    'bootstrappers' => [],
                ],
            ])
        );

        try {
            $app->boot();
            $this->fail('no exception thrown when expected.');
        } catch (Throwable $e) {
            $this->assertStringContainsString(Bundle::class, $e->getMessage());
            $this->assertStringContainsString(stdClass::class, $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function plugins_are_loaded_in_the_order_they_are_declared_in_the_config(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir_with_bundles),
        );

        $app->boot();

        $this->assertTrue($app->config()->get('bundle1.configured'));
        $this->assertTrue($app->container()[Bundle1::class]->registered);
        $this->assertTrue($app->container()[Bundle1::class]->booted);

        $this->assertTrue($app->config()->get('bundle2.configured'));
        $this->assertTrue($app->container()[Bundle2::class]->registered);
        $this->assertTrue($app->container()[Bundle2::class]->booted);
    }

    /**
     * @test
     */
    public function plugin_methods_are_called_in_the_correct_order(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir_with_bundles),
        );

        $app->boot();

        $this->assertTrue($app->config()->get('bundle_that_asserts_order.configured'));
        $this->assertTrue($app->container()[BundleAssertsMethodOrder::class]->registered);
        $this->assertTrue($app->container()[BundleAssertsMethodOrder::class]->booted);
    }

    /**
     * @test
     */
    public function plugins_can_only_run_in_some_environments(): void
    {
        $app1 = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
            new FixedBootstrapCache([
                'kernel' => [
                    'bundles' => [
                        Environment::ALL => [BundleWithCustomEnv::class],
                    ],
                    'bootstrappers' => [],
                ],
            ])
        );

        $app1->boot();

        $this->assertFalse(
            isset($app1->container()[BundleWithCustomEnv::class]),
            'bundle was booted when it should not be.'
        );

        $app2 = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
            new FixedBootstrapCache([
                'kernel' => [
                    'bundles' => [
                        Environment::ALL => [BundleWithCustomEnv::class],
                    ],
                    'bootstrappers' => [],
                ],
            ])
        );

        /** @psalm-suppress MixedArrayAssignment */
        $_SERVER['_test']['custom_env_bundle_should_run'] = true;

        $app2->boot();

        $this->assertTrue(
            $app2->container()[BundleWithCustomEnv::class]
                ->booted,
            'bundle was not booted when it should be.'
        );
        $this->assertTrue(
            $app2->container()[BundleWithCustomEnv::class]
                ->registered,
            'bundle was not registered when it should be.'
        );
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_two_plugins_use_the_same_alias(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                "2 bundles in your application share the same alias [duplicate_alias].\nAffected [%s]",
                implode(',', [BundleDuplicate1::class, BundleDuplicate2::class])
            )
        );

        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
            new FixedBootstrapCache([
                'kernel' => [
                    'bundles' => [
                        Environment::ALL => [BundleDuplicate1::class, BundleDuplicate2::class],
                    ],
                    'bootstrappers' => [],
                ],
            ])
        );

        $app->boot();
    }

    /**
     * @test
     */
    public function the_configure_method_will_not_be_called_if_the_configuration_is_cached(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
            new FixedBootstrapCache([
                'kernel' => [
                    'bundles' => [
                        Environment::ALL => [BundleThatConfigures::class],
                    ],
                    'bootstrappers' => [],
                ],
            ])
        );

        $app->boot();

        // configure is not called.
        try {
            $app->config()
                ->get('app.configured_value');
            $this->fail('Bundle::configure should not be called for a cached configuration.');
        } catch (MissingConfigKey $e) {
            $this->assertStringContainsString('app.configured_value', $e->getMessage());
        }

        // register was called
        $this->assertTrue($app->container()[BundleThatConfigures::class]->registered);
        // boot was called
        $this->assertTrue($app->container()[BundleThatConfigures::class]->booted);
        // plugin is used
        $this->assertTrue($app->usesBundle('bundle_that_configures'));
    }
}

final class BundleThatConfigures implements Bundle
{
    public bool $registered = false;

    public bool $booted = false;

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->set('app.configured_value', 'bar');
    }

    public function register(Kernel $kernel): void
    {
        $instance = new self();
        $instance->registered = true;

        $kernel->container()[self::class] = $instance;
    }

    public function alias(): string
    {
        return 'bundle_that_configures';
    }

    public function bootstrap(Kernel $kernel): void
    {
        $kernel->container()[self::class]
            ->booted = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }
}

final class BundleWithCustomEnv implements Bundle
{
    public bool $registered = false;

    public bool $booted = false;

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
        $instance = new self();
        $instance->registered = true;
        $kernel->container()[self::class] = $instance;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $kernel->container()[self::class]
            ->booted = true;
    }

    public function alias(): string
    {
        return 'custom_env_bundle';
    }

    public function shouldRun(Environment $env): bool
    {
        return isset($_SERVER['_test']['custom_env_bundle_should_run'])
            && true === $_SERVER['_test']['custom_env_bundle_should_run'];
    }
}

final class BundleDuplicate1 implements Bundle
{
    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return 'duplicate_alias';
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }
}

final class BundleDuplicate2 implements Bundle
{
    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return 'duplicate_alias';
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }
}
