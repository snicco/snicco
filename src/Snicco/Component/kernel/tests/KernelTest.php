<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Snicco\Component\Kernel\Configuration\ReadOnlyConfig;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Exception\ContainerIsLocked;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\Tests\helpers\CleanDirs;
use Snicco\Component\Kernel\Tests\helpers\CreateTestContainer;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

final class KernelTest extends TestCase
{

    use CreateTestContainer;
    use CleanDirs;

    private string $base_dir;

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

    /**
     * @test
     */
    public function test_construct(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        $this->assertInstanceOf(Kernel::class, $app);
    }

    /**
     * @test
     */
    public function test_env_returns_environment(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            $env = Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->assertEquals($env, $app->env());
    }

    /**
     * @test
     */
    public function the_environment_is_not_saved_in_the_container_so_that_it_cannot_be_overwritten(): void
    {
        new Kernel(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->assertFalse($container->has(Environment::class));
    }

    /**
     * @test
     */
    public function the_container_is_accessible(): void
    {
        $app = new Kernel(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );
        $this->assertSame($container, $app->container());
    }

    /**
     * @test
     */
    public function the_app_dirs_are_accessible(): void
    {
        $app = new Kernel(
            $container = $this->createContainer(),
            Environment::testing(),
            $dirs = Directories::fromDefaults($this->base_dir)
        );
        $this->assertSame($container, $app->container());

        $this->assertEquals($dirs, $app->directories());
    }

    /**
     * @test
     */
    public function the_container_is_bound_as_the_psr_container_interface(): void
    {
        $app = new Kernel(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->assertSame($container, $app->container()[ContainerInterface::class]);
    }

    /**
     * @test
     */
    public function the_kernel_cant_be_bootstrapped_twice(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $app->boot();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The kernel cant be booted twice.');

        $app->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_app_php_config_file_is_not_found(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir . '/base_dir_without_app_config')
        );

        $this->expectException(InvalidArgumentException::class);

        $dir = $this->base_dir . '/base_dir_without_app_config/config';

        $this->expectExceptionMessage(
            "The [app.php] config file was not found in the config dir [$dir]."
        );

        $app->boot();
    }

    /**
     * @test
     */
    public function the_config_method_on_the_app_returns_are_read_only_config(): void
    {
        $app = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        try {
            $app->config();
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

    /**
     * @test
     */
    public function offset_set_throws_after_booting(): void
    {
        $app = new Kernel(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $app->boot();

        $this->expectException(ContainerIsLocked::class);

        $container['foo'] = 'bar';
    }

    /**
     * @test
     */
    public function offset_unset_throws_after_booting(): void
    {
        $app = new Kernel(
            $container = $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $container['foo'] = 'bar';

        $app->boot();

        $this->expectException(ContainerIsLocked::class);

        unset($container['foo']);
    }

    /**
     * @test
     */
    public function after_register_callbacks_can_be_added(): void
    {
        $kernel = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()->primitive('foo', 'bar');
        });

        $this->assertFalse($kernel->container()->has('foo'));

        $kernel->boot();

        $this->assertSame('bar', $kernel->container()->get('foo'));
    }

    /**
     * @test
     */
    public function after_configuration_callbacks_can_be_added(): void
    {
        $kernel = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $kernel->afterConfiguration(function (WritableConfig $config, Kernel $kernel) {
            $config->set('foo', $kernel->env()->asString());
        });

        $kernel->boot();

        $this->assertSame('testing', $kernel->config()->get('foo'));
    }

    /**
     * @test
     */
    public function test_before_configuration_callbacks_can_be_added(): void
    {
        $kernel = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $kernel->beforeConfiguration(function (WritableConfig $config, Kernel $kernel) {
            $config->set('foo', $kernel->env()->asString());
        });

        $kernel->boot();

        $this->assertSame('testing', $kernel->config()->get('foo'));
    }

    /**
     * @test
     */
    public function test_exception_if_after_register_callback_is_added_after_kernel_is_booted(): void
    {
        $kernel = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $kernel->boot();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('register callbacks can not be added after the kernel was booted.');

        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()->primitive('foo', 'bar');
        });
    }

    /**
     * @test
     */
    public function test_exception_if_after_configuration_callback_is_added_after_kernel_is_booted(): void
    {
        $kernel = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $kernel->boot();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('configuration callbacks can not be added after the kernel was booted.');

        $kernel->afterConfiguration(function () {
        });
    }

    /**
     * @test
     */
    public function test_exception_if_before_configuration_callback_is_added_after_kernel_is_booted(): void
    {
        $kernel = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $kernel->boot();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('configuration callbacks can not be added after the kernel was booted.');

        $kernel->beforeConfiguration(function () {
        });
    }


}
