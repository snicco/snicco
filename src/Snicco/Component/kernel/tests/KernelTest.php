<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests;

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
use stdClass;

/**
 * @internal
 */
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
            $this->assertStringContainsString('only be accessed after bootstrapping.', $e->getMessage());
        }

        $app->boot();
        $config = $app->config();
        $this->assertInstanceOf(ReadOnlyConfig::class, $config);
        $this->assertSame('bar', $config->get('foo.foo'));
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

        $container[stdClass::class] = new stdClass();
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

        $container[stdClass::class] = new stdClass();

        $app->boot();

        $this->expectException(ContainerIsLocked::class);

        unset($container[stdClass::class]);
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

        $kernel->afterRegister(function (Kernel $kernel): void {
            $kernel->container()
                ->instance(stdClass::class, new stdClass());
        });

        $this->assertFalse($kernel->container()->has(stdClass::class));

        $kernel->boot();

        $this->assertTrue($kernel->container()->has(stdClass::class));
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

        $kernel->afterConfigurationLoaded(function (WritableConfig $config, Kernel $kernel): void {
            $config->set('foo', $kernel->env()->asString());
        });

        $kernel->boot();

        $this->assertSame('testing', $kernel->config()->get('foo'));
    }

    /**
     * @test
     */
    public function that_calling_after_configuration_loaded_to_late_throws_an_exception(): void
    {
        $kernel = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $kernel->afterRegister(function (Kernel $kernel): void {
            $kernel->afterConfigurationLoaded(function (WritableConfig $config, Kernel $kernel): void {
                $config->set('foo', $kernel->env()->asString());
            });
        });

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            '::afterConfigurationLoaded can not be called from inside a bundle or bootstrapper.'
        );

        $kernel->boot();
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

        $kernel->afterRegister(function (): void {
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

        $kernel->afterConfigurationLoaded(function (): void {
        });
    }

    /**
     * @test
     */
    public function test_booted(): void
    {
        $kernel = new Kernel(
            $this->createContainer(),
            Environment::testing(),
            Directories::fromDefaults($this->base_dir)
        );

        $this->assertFalse($kernel->booted());

        $kernel->boot();

        $this->assertTrue($kernel->booted());
    }
}
