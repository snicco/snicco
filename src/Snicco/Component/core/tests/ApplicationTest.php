<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Snicco\Component\Core\Application;
use Snicco\Component\Core\Configuration\ReadOnlyConfig;
use Snicco\Component\Core\Directories;
use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Exception\ContainerIsLocked;
use Snicco\Component\Core\Tests\helpers\CreateTestContainer;
use Snicco\Component\Core\Tests\helpers\WriteTestConfig;

final class ApplicationTest extends TestCase
{

    use CreateTestContainer;
    use WriteTestConfig;

    private string $base_dir;

    /** @test */
    public function test_construct()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        $this->assertInstanceOf(Application::class, $app);
    }

    /** @test */
    public function test_array_access_proxies_to_set_container()
    {
        $container = $this->createContainer();
        $container['foo'] = 'bar';

        $app = new Application(
            $container,
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->assertTrue(isset($app['foo']));
        $this->assertTrue(isset($container['foo']));
        $this->assertFalse(isset($app['baz']));
        $this->assertFalse(isset($container['baz']));

        $this->assertSame('bar', $app['foo']);
        $this->assertSame('bar', $container['foo']);

        unset($app['foo']);
        $this->assertFalse(isset($app['foo']));
        $this->assertFalse(isset($container['foo']));

        $app['baz'] = 'biz';

        $this->assertTrue(isset($app['baz']));
        $this->assertTrue(isset($container['baz']));
    }

    /** @test */
    public function test_env_returns_environment()
    {
        $app = new Application(
            $this->createContainer(),
            $env = Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->assertEquals($env, $app->env());
    }

    /** @test */
    public function the_environment_is_not_saved_in_the_container_so_that_it_cannot_be_overwritten()
    {
        $app = new Application(
            $container = $this->createContainer(),
            $env = Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->assertFalse($container->has(Environment::class));
    }

    /** @test */
    public function the_container_is_accessible()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        $this->assertSame($container, $app->di());
    }

    /** @test */
    public function the_app_dirs_are_accessible()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            $dirs = Directories::fromDefaults($this->base_dir)
        );
        $this->assertSame($container, $app->di());

        $this->assertEquals($dirs, $app->directories());
    }

    /** @test */
    public function the_container_is_bound_as_the_psr_container_interface()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->assertSame($container, $app[ContainerInterface::class]);
    }

    /** @test */
    public function the_application_cant_be_bootstrapped_twice()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $app->boot();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The application cant be booted twice.');

        $app->boot();
    }

    /** @test */
    public function test_exception_if_app_php_config_file_is_not_found()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir . '/base_dir_without_app_config')
        );

        $this->expectException(RuntimeException::class);

        $dir = $this->base_dir . '/base_dir_without_app_config/config';

        $this->expectExceptionMessage(
            "The [app.php] config file was not found in the config dir [$dir]."
        );

        $app->boot();
    }

    /** @test */
    public function the_config_method_on_the_app_returns_are_read_only_config()
    {
        $app = new Application(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        try {
            $config = $app->config();
        } catch (LogicException $e) {
            $this->assertStringContainsString(
                'only be accessed after bootstrapping.',
                $e->getMessage()
            );
        }

        $app->boot();
        $config = $app->config();
        $this->assertInstanceOf(ReadOnlyConfig::class, $config);
        $this->assertSame('bar', $config->get('app.foo'));
    }

    /** @test */
    public function offset_set_throws_after_booting()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $app->boot();

        $this->expectException(ContainerIsLocked::class);

        $container['foo'] = 'bar';
    }

    /** @test */
    public function offset_unset_throws_after_booting()
    {
        $app = new Application(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $container['foo'] = 'bar';

        $app->boot();

        $this->expectException(ContainerIsLocked::class);

        unset($container['foo']);
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
