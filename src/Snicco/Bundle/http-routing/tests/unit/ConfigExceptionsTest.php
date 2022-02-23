<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\HttpRouting\Option\HttpErrorHandlingOption;
use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\HttpRouting\Tests\unit\fixtures\Middleware\MiddlewareOne;
use Snicco\Bundle\HttpRouting\Tests\unit\fixtures\Middleware\MiddlewareTwo;
use Snicco\Bundle\Testing\BootsKernelForBundleTest;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;
use Snicco\Component\Psr7ErrorHandler\Log\RequestLogContext;
use stdClass;

final class ConfigExceptionsTest extends TestCase
{
    use BootsKernelForBundleTest;

    private Directories $directories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directories = Directories::fromDefaults(__DIR__ . '/fixtures');
    }

    /**
     * @test
     */
    public function test_exception_if_wp_admin_prefix_is_not_a_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RoutingOption::WP_ADMIN_PREFIX);

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
                RoutingOption::WP_ADMIN_PREFIX => ''
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_if_wp_login_path_is_not_a_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RoutingOption::WP_LOGIN_PATH);

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
                RoutingOption::WP_LOGIN_PATH => ''
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_if_route_directories_is_not_list_of_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RoutingOption::ROUTE_DIRECTORIES);

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
                RoutingOption::ROUTE_DIRECTORIES => ['foo' => 'bar']
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_if_route_directory_is_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__DIR__ . '/bogus');

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
                RoutingOption::ROUTE_DIRECTORIES => [__DIR__, __DIR__ . '/bogus']
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_if_api_route_directory_is_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__DIR__ . '/bogus');

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
                RoutingOption::API_ROUTE_DIRECTORIES => [__DIR__, __DIR__ . '/bogus']
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_for_middleware_groups_non_string_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MiddlewareOption::GROUPS);

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
            ],
            'middleware' => [
                MiddlewareOption::GROUPS => ['foo']
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_for_non_array_middleware_group(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Got [string] for key [foo]');

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
            ],
            'middleware' => [
                MiddlewareOption::GROUPS => ['foo' => 'bar']
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_for_not_all_stings_in_middleware_group(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Middleware group [foo] has to contain only strings.\nGot [integer] at index [1]."
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
            ],
            'middleware' => [
                MiddlewareOption::GROUPS => ['foo' => ['bar', 1]]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_for_middleware_aliases_not_string_class_string_pairs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'middleware.middleware_aliases has to be an array of string => middleware-class pairs'
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
            ],
            'middleware' => [
                MiddlewareOption::ALIASES => ['foo' => MiddlewareOne::class, MiddlewareTwo::class]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_for_middleware_alias_non_middleware_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Middleware alias [foo] has to resolve to a middleware class.'
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',

            ],
            'middleware' => [
                MiddlewareOption::ALIASES => ['foo' => stdClass::class]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_for_middleware_priority_not_list_of_class_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            MiddlewareOption::PRIORITY_LIST . " has to be a list of middleware class-strings.\nGot [stdClass]."
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
            ],
            'middleware' => [
                MiddlewareOption::PRIORITY_LIST => [
                    MiddlewareOne::class,
                    MiddlewareTwo::class,
                    stdClass::class,
                ]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_for_always_run_not_allowed_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            MiddlewareOption::ALWAYS_RUN . " can only contain [frontend,api,admin,global].\nGot [foo]."
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
            ],
            'middleware' => [
                MiddlewareOption::ALWAYS_RUN => [
                    'frontend',
                    'admin',
                    'api',
                    'foo'
                ]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_if_exception_displayers_not_lists_of_class_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            HttpErrorHandlingOption::DISPLAYERS . ' has to be a list of class-strings implementing ' . ExceptionDisplayer::class . ".\nGot [stdClass]."
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',

            ],
            'http_error_handling' => [
                HttpErrorHandlingOption::DISPLAYERS => [
                    stdClass::class
                ]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_if_exception_transformers_not_lists_of_class_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            HttpErrorHandlingOption::TRANSFORMERS . ' has to be a list of class-strings implementing ' . ExceptionTransformer::class . ".\nGot [stdClass]."
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',

            ],
            'http_error_handling' => [
                HttpErrorHandlingOption::TRANSFORMERS => [
                    stdClass::class
                ]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_if_request_contexts_not_lists_of_class_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            HttpErrorHandlingOption::REQUEST_LOG_CONTEXT . ' has to be a list of class-strings implementing ' . RequestLogContext::class . ".\nGot [stdClass]."
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
            ],
            'http_error_handling' => [
                HttpErrorHandlingOption::REQUEST_LOG_CONTEXT => [
                    stdClass::class
                ]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_for_invalid_class_in_log_levels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '[stdClass] is not a valid exception class-string for ' . HttpErrorHandlingOption::key(
                HttpErrorHandlingOption::LOG_LEVELS
            )
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',

            ],
            'http_error_handling' => [
                HttpErrorHandlingOption::LOG_LEVELS => [
                    HttpException::class => LogLevel::ERROR,
                    stdClass::class => LogLevel::CRITICAL
                ]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_for_invalid_log_level(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '[bogus] is not a valid PSR-3 log-level for exception class ' . HttpException::class
        );

        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            $this->directories
        );

        $bundle = new HttpRoutingBundle();

        $config = WritableConfig::fromArray([
            'routing' => [
                'host' => 'foo.com',
            ],
            'http_error_handling' => [
                HttpErrorHandlingOption::LOG_LEVELS => [
                    HttpException::class => 'bogus',
                ]
            ]
        ]);

        $bundle->configure($config, $kernel);
    }

    protected function bundles(): array
    {
        return [
            Environment::ALL => [
                HttpRoutingBundle::class
            ]
        ];
    }

}