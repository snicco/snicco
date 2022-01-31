<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Bootstrapper;
use Snicco\Component\Core\Bundle;
use Snicco\Component\Core\Configuration\WritableConfig;
use Snicco\Component\Core\Directories;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Exception\ContainerIsLocked;
use Snicco\Component\Core\Tests\helpers\CreateTestContainer;
use Snicco\Component\Core\Tests\helpers\WriteTestConfig;
use stdClass;

final class ApplicationBootstrappersTest extends TestCase
{

    use CreateTestContainer;
    use WriteTestConfig;

    private string $base_dir;

    /** @test */
    public function bootstrappers_are_loaded_from_the_app_bootstrapper_key()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->writeConfig($app, [
            'app' => [
                'bootstrappers' => [
                    Bootstrap1::class,
                ],
            ],
        ]);

        $app->boot();

        $this->assertTrue($app['bootstrapper_1_registered']);
        $this->assertTrue($app['bootstrapper_1_booted']->val);
    }

    /** @test */
    public function bootstrappers_are_loaded_after_external_bundles()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->writeConfig($app, [
            'app' => [
                'bootstrappers' => [
                    Bootstrap2::class,
                ],
            ],
            'bundles' => [
                BundleInfo::class => ['all' => true],
            ],
        ]);

        $app->boot();

        $this->assertTrue($app->di()->get('bundle_info_registered'));
        $this->assertTrue($app->di()->get('bundle_info_booted')->val);

        $this->assertTrue($app->di()->get('bootstrapper_2_registered'));
        $this->assertTrue($app->di()->get('bootstrapper_2_booted')->val);
    }

    /** @test */
    public function an_exception_is_thrown_if_the_container_is_modified_after_the_register_method()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::prod(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->writeConfig($app, [
            'app' => [
                'bootstrappers' => [
                    BootrstrapperWithExceptionInBoostrap::class,
                ],
            ],

        ]);

        $this->expectException(ContainerIsLocked::class);
        $this->expectExceptionMessage('id [foo]');
        $app->boot();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = __DIR__ . '/fixtures';
        $this->cleanDirs([$this->base_dir . '/var/cache']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanDirs([$this->base_dir . '/var/cache']);
    }

}

class BundleInfo implements Bundle
{

    public function runsInEnvironments(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Application $app): void
    {
    }

    public function register(Application $app): void
    {
        $app['bundle_info_registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $app['bundle_info_booted'] = $std;
    }

    public function bootstrap(Application $app): void
    {
        $app['bundle_info_booted']->val = true;
    }

    public function alias(): string
    {
        return 'bundle_info';
    }

}

class Bootstrap1 implements Bootstrapper
{

    public function configure(WritableConfig $config, Application $app): void
    {
        $config->set('bootstrapper1_configured', true);
    }

    public function register(Application $app): void
    {
        $app['bootstrapper_1_registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $app['bootstrapper_1_booted'] = $std;
    }

    public function bootstrap(Application $app): void
    {
        $app['bootstrapper_1_booted']->val = true;
    }

    public function runsInEnvironments(Environment $env): bool
    {
        return true;
    }

}

class Bootstrap2 implements Bootstrapper
{

    public function configure(WritableConfig $config, Application $app): void
    {
        //
    }

    public function register(Application $app): void
    {
        if (!$app->di()->has('bundle_info_registered')) {
            throw new RuntimeException('Bootstrapper registered before bundle');
        }
        $app['bootstrapper_2_registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $app['bootstrapper_2_booted'] = $std;
    }

    public function bootstrap(Application $app): void
    {
        if (!$app['bundle_info_booted']->val === true) {
            throw new RuntimeException('Bootstrapper bootstrapped before bundle');
        }
        $app['bootstrapper_2_booted']->val = true;
    }

    public function runsInEnvironments(Environment $env): bool
    {
        return true;
    }

}

class BootrstrapperWithExceptionInBoostrap implements Bootstrapper
{

    public function runsInEnvironments(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Application $app): void
    {
        //
    }

    public function register(Application $app): void
    {
        //
    }

    public function bootstrap(Application $app): void
    {
        $app['foo'] = 'bar';
    }

}
