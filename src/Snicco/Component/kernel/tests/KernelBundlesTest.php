<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Exception\MissingConfigKey;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\Tests\helpers\CreateTestContainer;
use Snicco\Component\Kernel\Tests\helpers\WriteTestConfig;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use stdClass;
use Throwable;

final class KernelBundlesTest extends TestCase
{

    use CreateTestContainer;
    use WriteTestConfig;

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
            Directories::fromDefaults($this->base_dir)
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
            Directories::fromDefaults($this->base_dir_with_bundles)
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
        );

        $this->writeConfig($app, [
            'app' => [

            ],
            'bundles' => [
                Environment::PROD => [stdClass::class]
            ],
        ]);

        try {
            $app->boot();
            $this->fail('no exception thrown');
        } catch (Throwable $e) {
            $this->assertStringContainsString("Snicco\\Component\\Kernel\\Bundle", $e->getMessage());
            $this->assertStringContainsString('stdClass', $e->getMessage());
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
        $this->assertTrue($app->container()['bundle1.registered']);
        $this->assertTrue($app->container()['bundle1.booted']->val);

        $this->assertTrue($app->config()->get('bundle2.configured'));
        $this->assertTrue($app->container()['bundle2.registered']);
        $this->assertTrue($app->container()['bundle2.booted']->val);
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
        $this->assertTrue($app->container()['bundle_that_asserts_order.registered']);
        $this->assertTrue($app->container()['bundle_that_asserts_order.bootstrapped']->val);
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
        );

        $this->writeConfig($app1, [
            'bundles' => [
                Environment::ALL => [
                    BundleWithCustomEnv::class,
                ]
            ],
        ]);

        $app1->boot();

        $this->assertFalse(
            isset($app1->container()['custom_env_bundle_run']),
            'bundle was booted when it should not be.'
        );

        $app2 = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
        );

        $this->writeConfig($app2, [
            'bundles' => [
                Environment::ALL => [
                    BundleWithCustomEnv::class,
                ]
            ],
        ]);

        $_SERVER['_test']['custom_env_bundle_should_run'] = true;

        $app2->boot();

        $this->assertTrue(
            $app2->container()['custom_env_bundle_run']->val,
            'bundle was not booted when it should be.'
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
        );

        $this->writeConfig($app, [
            'bundles' => [
                Environment::ALL => [
                    BundleDuplicate1::class,
                    BundleDuplicate2::class
                ]
            ],
        ]);

        $app->boot();
    }

    /**
     * @test
     */
    public function the_configure_method_will_not_called_if_the_configuration_is_cached(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir),
        );

        $this->writeConfig($app, [
            'bundles' => [
                Environment::ALL => [
                    BundleThatConfigures::class
                ]
            ],
        ]);

        $app->boot();

        // configure is not called.
        try {
            $app->config()->get('app.configured_value');
            $this->fail('Bundle::configure should not be called for a cached configuration.');
        } catch (MissingConfigKey $e) {
            $this->assertStringContainsString('app.configured_value', $e->getMessage());
        }

        // register was called
        $this->assertTrue($app->container()['bundle_that_configures.registered']);
        // boot was called
        $this->assertTrue($app->container()['bundle_that_configures.booted']->val);
        // plugin is used
        $this->assertTrue($app->usesBundle('bundle_that_configures'));
    }

}


class BundleThatConfigures implements Bundle
{

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->set('app.configured_value', 'bar');
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container()[$this->alias() . '.registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $kernel->container()[$this->alias() . '.booted'] = $std;
    }

    public function alias(): string
    {
        return 'bundle_that_configures';
    }

    public function bootstrap(Kernel $kernel): void
    {
        $kernel->container()[$this->alias() . '.booted']->val = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

}

class BundleWithCustomEnv implements Bundle
{

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        //
    }

    public function register(Kernel $kernel): void
    {
        $std = new stdClass();
        $std->val = false;
        $kernel->container()['custom_env_bundle_run'] = $std;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $kernel->container()['custom_env_bundle_run']->val = true;
    }

    public function alias(): string
    {
        return 'custom_env_bundle';
    }

    public function shouldRun(Environment $env): bool
    {
        return isset($_SERVER['_test']['custom_env_bundle_should_run'])
            && $_SERVER['_test']['custom_env_bundle_should_run'] === true;
    }

}

class BundleDuplicate1 implements Bundle
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

class BundleDuplicate2 implements Bundle
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