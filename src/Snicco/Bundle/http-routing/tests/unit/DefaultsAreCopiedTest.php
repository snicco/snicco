<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit;

use PHPUnit\Framework\TestCase;
use Snicco\Bundle\HttpRouting\Option\HttpErrorHandlingOption;
use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\Testing\BundleTestHelpers;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;
use function file_put_contents;
use function is_file;
use function var_export;

/**
 * @psalm-suppress UnresolvableInclude
 */
final class DefaultsAreCopiedTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function the_default_routing_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $this->assertFalse(is_file($this->directories->configDir() . '/routing.php'));

        $kernel->boot();


        $this->assertTrue(is_file($this->directories->configDir() . '/routing.php'));

        $this->assertSame(
            require dirname(__DIR__, 2) . '/config/routing.php',
            require $this->directories->configDir() . '/routing.php'
        );
    }

    /**
     * @test
     */
    public function the_default_routing_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        file_put_contents(
            $this->directories->configDir() . '/routing.php',
            '<?php return ' . var_export([RoutingOption::HOST => 'foo.com'], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/routing.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame(
            [RoutingOption::HOST => 'foo.com'],
            require $this->directories->configDir() . '/routing.php'
        );
        $this->assertSame(
            'foo.com',
            $kernel->config()->get('routing.' . RoutingOption::HOST)
        );
    }

    /**
     * @test
     */
    public function the_default_routing_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );

        $this->assertFalse(is_file($this->directories->configDir() . '/routing.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/routing.php'));
    }

    /**
     * @test
     */
    public function the_default_middleware_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $this->assertFalse(is_file($this->directories->configDir() . '/middleware.php'));

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/middleware.php'));

        $this->assertSame(
            require dirname(__DIR__, 2) . '/config/middleware.php',
            require $this->directories->configDir() . '/middleware.php'
        );
    }

    /**
     * @test
     */
    public function the_default_middleware_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        file_put_contents(
            $this->directories->configDir() . '/middleware.php',
            '<?php return ' . var_export([MiddlewareOption::ALWAYS_RUN => ['frontend']], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/middleware.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame(
            [MiddlewareOption::ALWAYS_RUN => ['frontend']],
            require $this->directories->configDir() . '/middleware.php'
        );
        $this->assertSame(
            ['frontend'],
            $kernel->config()->get('middleware.' . MiddlewareOption::ALWAYS_RUN)
        );
    }

    /**
     * @test
     */
    public function the_default_middleware_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );

        $this->assertFalse(is_file($this->directories->configDir() . '/middleware.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/middleware.php'));
    }

    /**
     * @test
     */
    public function the_default_error_handling_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(
    ): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $this->assertFalse(is_file($this->directories->configDir() . '/http_error_handling.php'));

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/http_error_handling.php'));

        $this->assertSame(
            require dirname(__DIR__, 2) . '/config/http_error_handling.php',
            require $this->directories->configDir() . '/http_error_handling.php'
        );
    }

    /**
     * @test
     */
    public function the_default_error_handling_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        file_put_contents(
            $this->directories->configDir() . '/http_error_handling.php',
            '<?php return ' . var_export([HttpErrorHandlingOption::LOG_PREFIX => 'custom.request'], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/http_error_handling.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame(
            [HttpErrorHandlingOption::LOG_PREFIX => 'custom.request'],
            require $this->directories->configDir() . '/http_error_handling.php'
        );
        $this->assertSame(
            'custom.request',
            $kernel->config()->get('http_error_handling.' . HttpErrorHandlingOption::LOG_PREFIX)
        );
    }

    /**
     * @test
     */
    public function the_default_error_handling_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );

        $this->assertFalse(is_file($this->directories->configDir() . '/http_error_handling.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/http_error_handling.php'));
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/default-configs-test';
    }


}