<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Snicco\Bundle\HttpRouting\Option\HttpErrorHandlingOption;
use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\HttpRouting\Tests\fixtures\Middleware\MiddlewareOne;
use Snicco\Bundle\HttpRouting\Tests\fixtures\Middleware\MiddlewareTwo;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\Router;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;
use Snicco\Component\Psr7ErrorHandler\Log\RequestLogContext;
use stdClass;

use Stringable;
use Throwable;
use function dirname;

/**
 * @internal
 */
final class ConfigExceptionsTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_exception_if_routing_host_is_not_set(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing', [
                RoutingOption::HOST => '',
            ]);
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('routing.host must be a non-empty-string');

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_wp_admin_prefix_is_not_a_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RoutingOption::WP_ADMIN_PREFIX);

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.' . RoutingOption::WP_ADMIN_PREFIX, '');
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_wp_login_path_is_not_a_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RoutingOption::WP_LOGIN_PATH);

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.' . RoutingOption::WP_LOGIN_PATH, '');
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_route_directories_is_not_list_of_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RoutingOption::ROUTE_DIRECTORIES);

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.' . RoutingOption::ROUTE_DIRECTORIES, [
                'foo' => 'bar',
            ]);
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_route_directory_is_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__DIR__ . '/bogus');

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.' . RoutingOption::ROUTE_DIRECTORIES, [__DIR__, __DIR__ . '/bogus']);
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_relative_route_directory_is_not_readable(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.' . RoutingOption::ROUTE_DIRECTORIES, [__DIR__, 'routes', 'bogus']);
        });

        try {
            $kernel->boot();
            $this->fail('Test should have failed here');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith(
                'routing.route_directories must be a list of readable directories',
                $e->getMessage()
            );
            $this->assertStringContainsString('bogus', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function test_exception_if_api_route_directory_is_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__DIR__ . '/bogus');

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.' . RoutingOption::API_ROUTE_DIRECTORIES, [__DIR__, __DIR__ . '/bogus']);
            $config->set('routing.' . RoutingOption::API_PREFIX, 'snicco');
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_relative_api_route_directory_is_not_readable(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.' . RoutingOption::API_ROUTE_DIRECTORIES, [__DIR__, 'routes', 'bogus']);
            $config->set('routing.' . RoutingOption::API_PREFIX, 'snicco');
        });

        try {
            $kernel->boot();
            $this->fail('Test should have failed here');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith(
                'routing.api_route_directories must be a list of readable directories',
                $e->getMessage()
            );
            $this->assertStringContainsString('bogus', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function test_exception_if_api_routes_are_set_but_no_api_prefix_is_set(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'routing.api_prefix must be a non-empty-string if routing.early_routes_prefixes is empty.'
        );

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing', [
                RoutingOption::HOST => 'snicco.com',
                RoutingOption::API_ROUTE_DIRECTORIES => [__DIR__],
            ]);
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_no_exception_if_api_routes_are_set_but_no_api_prefix_is_set_and_early_routes_are_set(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing', [
                RoutingOption::HOST => 'snicco.com',
                RoutingOption::API_ROUTE_DIRECTORIES => [__DIR__],
                RoutingOption::EARLY_ROUTES_PREFIXES => ['/foo'],
            ]);
        });

        $kernel->boot();

        $this->assertInstanceOf(Router::class, $kernel->container()->make(Router::class));
    }

    /**
     * @test
     */
    public function test_exception_for_middleware_groups_non_string_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MiddlewareOption::GROUPS);

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('middleware.' . MiddlewareOption::GROUPS, ['foo']);
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_for_non_array_middleware_group(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Got [string] for key [foo]');

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('middleware.' . MiddlewareOption::GROUPS, [
                'foo' => 'bar',
            ]);
        });

        $kernel->boot();
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

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('middleware.' . MiddlewareOption::GROUPS, [
                'foo' => ['bar', 1],
            ]);
        });

        $kernel->boot();
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

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set(
                'middleware.' . MiddlewareOption::ALIASES,
                [
                    'foo' => MiddlewareOne::class,
                    MiddlewareTwo::class,
                ]
            );
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_for_middleware_alias_non_middleware_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware alias [foo] has to resolve to a middleware class.');

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('middleware.' . MiddlewareOption::ALIASES, [
                'foo' => stdClass::class,
            ]);
        });

        $kernel->boot();
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

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set(
                'middleware.' . MiddlewareOption::PRIORITY_LIST,
                [MiddlewareOne::class, MiddlewareTwo::class, stdClass::class]
            );
        });

        $kernel->boot();
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

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('middleware.' . MiddlewareOption::ALWAYS_RUN, ['frontend', 'admin', 'api', 'foo']);
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_kernel_middleware_not_all_middleware_interface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('middleware.kernel_middleware has to be a list of middleware class-strings');

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set(
                'middleware.' . MiddlewareOption::KERNEL_MIDDLEWARE,
                [RoutingMiddleware::class, RouteRunner::class, stdClass::class]
            );
        });

        $kernel->boot();
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

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('http_error_handling.' . HttpErrorHandlingOption::DISPLAYERS, [stdClass::class]);
        });

        $kernel->boot();
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

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('http_error_handling.' . HttpErrorHandlingOption::TRANSFORMERS, [stdClass::class]);
        });

        $kernel->boot();
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

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('http_error_handling.' . HttpErrorHandlingOption::REQUEST_LOG_CONTEXT, [stdClass::class]);
        });

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_for_invalid_class_in_log_levels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[bogus-bogus] is not a valid class-string for ');

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('http_error_handling', [
                HttpErrorHandlingOption::LOG_LEVELS => [
                    HttpException::class => LogLevel::ERROR,
                    Throwable::class => LogLevel::ERROR,
                    Stringable::class => LogLevel::ERROR,
                    'bogus-bogus' => LogLevel::CRITICAL,
                ],
            ]);
        });
        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_for_invalid_non_string_in_log_levels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string');

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('http_error_handling', [
                HttpErrorHandlingOption::LOG_LEVELS => [
                    1 => LogLevel::ERROR,
                ],
            ]);
        });
        $kernel->boot();
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

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('http_error_handling', [
                HttpErrorHandlingOption::LOG_LEVELS => [
                    HttpException::class => 'bogus',
                ],
            ]);
        });
        $kernel->boot();
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}
