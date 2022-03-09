<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\unit;

use LogicException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use RuntimeException;
use Snicco\Bundle\HttpRouting\HttpKernel;
use Snicco\Bundle\HttpRouting\Option\HttpErrorHandlingOption;
use Snicco\Bundle\HttpRouting\StdErrLogger;
use Snicco\Bundle\HttpRouting\Tests\fixtures\RoutingBundleTestController;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformationProvider;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;
use Snicco\Component\Psr7ErrorHandler\Log\RequestLogContext;
use Throwable;
use TypeError;

use function dirname;
use function restore_error_handler;
use function set_error_handler;
use function trigger_error;

use const E_USER_NOTICE;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 *
 * @internal
 */
final class ErrorHandlingTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function exceptions_are_handled_by_default_during_routing(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $response = $pipeline
            ->send(Request::fromPsr(new ServerRequest('GET', '/')))
            ->through([])
            ->then(function () {
                throw new RuntimeException('secret error in routing.');
            });

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('secret error in routing', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function a_test_logger_is_used_in_testing_environments(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $pipeline
            ->send(Request::fromPsr(new ServerRequest('GET', '/')))
            ->through([])
            ->then(function () {
                throw new TypeError('secret error in routing.');
            });

        /** @var TestLogger $logger */
        $logger = $kernel->container()->make(TestLogger::class);

        $this->assertTrue($logger->hasCriticalRecords());

        $this->assertSame($logger, $kernel->container()->make(LoggerInterface::class));
    }

    /**
     * @test
     */
    public function the_test_logger_is_not_bound_in_non_testing_env(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );

        $kernel->boot();

        $this->assertNotBound(TestLogger::class, $kernel);
    }

    /**
     * @test
     */
    public function request_log_context_can_be_added(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('http_error_handling', [
                HttpErrorHandlingOption::REQUEST_LOG_CONTEXT => [
                    PathLogContext::class,
                    QueryStringLogContext::class,
                ],
            ]);
        });

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $pipeline
            ->send(Request::fromPsr(new ServerRequest('GET', 'https://foo.com/bar?baz=biz')))
            ->through([])
            ->then(function () {
                throw new TypeError('secret error in routing.');
            });

        /** @var TestLogger $logger */
        $logger = $kernel->container()->make(TestLogger::class);

        $this->assertTrue(
            $logger->hasCritical([
                'message' => 'secret error in routing.',
                'path' => '/bar',
                'query_string' => 'baz=biz',
            ])
        );
    }

    /**
     * @test
     */
    public function custom_transformers_can_be_added(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('http_error_handling', [
                HttpErrorHandlingOption::TRANSFORMERS => [
                    Transformer2::class,
                    Transformer1::class,
                ],
            ]);
        });

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $response = $pipeline
            ->send(Request::fromPsr(new ServerRequest('GET', 'https://foo.com/bar?baz=biz')))
            ->through([])
            ->then(function () {
                throw new RuntimeException('error1');
            });

        // transformers are run in order
        $this->assertSame(404, $response->getStatusCode());

        $response = $pipeline
            ->send(Request::fromPsr(new ServerRequest('GET', 'https://foo.com/bar?baz=biz')))
            ->through([])
            ->then(function () {
                throw new LogicException('irrelevant');
            });

        // transformer1 acted.
        $this->assertSame(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function custom_displayers_can_be_added(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('http_error_handling', [
                HttpErrorHandlingOption::DISPLAYERS => [
                    CustomHtmlDisplayer::class,
                ],
            ]);
        });
        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('GET', 'https://foo.com/bar?baz=biz'));
        $request = $request->withHeader('accept', 'text/html');

        $response = $pipeline
            ->send($request)
            ->through([])
            ->then(function () {
                throw new TypeError('secret error in routing.');
            });

        $this->assertSame('foobar', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function custom_log_levels_can_be_used(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('http_error_handling', [
                HttpErrorHandlingOption::LOG_LEVELS => [
                    TypeError::class => LogLevel::WARNING,
                ],
            ]);
        });
        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $pipeline
            ->send(Request::fromPsr(new ServerRequest('GET', 'https://foo.com/bar?baz=biz')))
            ->through([])
            ->then(function () {
                throw new TypeError('secret error in routing.');
            });

        /** @var TestLogger $logger */
        $logger = $kernel->container()->make(TestLogger::class);

        $this->assertFalse($logger->hasCriticalRecords());
        $this->assertTrue($logger->hasWarningRecords());
    }

    /**
     * @test
     */
    public function in_production_the_std_error_logger_is_bound_if_not_already_set_in_the_container(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );
        $kernel->boot();

        $this->assertInstanceOf(StdErrLogger::class, $kernel->container()->make(LoggerInterface::class));

        $container = $this->newContainer();
        $container[LoggerInterface::class] = fn (): NullLogger => new NullLogger();

        $kernel = new Kernel(
            $container,
            Environment::prod(),
            $this->directories
        );
        $kernel->boot();

        $this->assertInstanceOf(NullLogger::class, $kernel->container()->make(LoggerInterface::class));
    }

    /**
     * @test
     */
    public function a_custom_exception_information_provider_can_be_used(): void
    {
        $container = $this->newContainer();
        $container[ExceptionInformationProvider::class] = function (): ExceptionInformationProvider {
            return new class() implements ExceptionInformationProvider {
                public function createFor(Throwable $e, ServerRequestInterface $request): ExceptionInformation
                {
                    return new ExceptionInformation(
                        500,
                        'foo_id',
                        'foo_title',
                        'foo_details',
                        $e,
                        $e,
                        $request,
                    );
                }
            };
        };

        $kernel = new Kernel(
            $container,
            Environment::prod(),
            $this->directories
        );
        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/foo');

        $response = $pipeline->send(Request::fromPsr($request))->through([])->then(function () {
            throw new RuntimeException('error');
        });

        $body = (string) $response->getBody();

        $this->assertStringContainsString('foo_id', $body);
        $this->assertStringContainsString('foo_title', $body);
        $this->assertStringContainsString('foo_details', $body);
    }

    /**
     * @test
     */
    public function errors_inside_the_routing_pipeline_are_converted_to_exceptions(): void
    {
        $handled_by_global_handler = false;
        set_error_handler(function () use (&$handled_by_global_handler): bool {
            $handled_by_global_handler = true;

            return false;
        });

        try {
            $kernel = new Kernel(
                $this->newContainer(),
                Environment::testing(),
                $this->directories
            );

            $kernel->boot();

            /** @var HttpKernel $http_kernel */
            $http_kernel = $kernel->container()->make(HttpKernel::class);

            $request = new ServerRequest('GET', '/trigger-notice');

            $response = $http_kernel->handle(Request::fromPsr($request));

            $body = (string) $response->getBody();

            $this->assertSame(500, $response->getStatusCode());
            $this->assertStringNotContainsString(RoutingBundleTestController::class, $body);
            $this->assertFalse(
                $handled_by_global_handler,
                'the error was handled by the global exception handler, not the kernel.'
            );

            /** @var TestLogger $logger */
            $logger = $kernel->container()->make(TestLogger::class);
            $this->assertTrue(
                $logger->hasCritical([
                    'message' => RoutingBundleTestController::class,
                ])
            );
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @test
     */
    public function the_error_handler_is_only_active_inside_the_routing_pipeline_and_restored_after_each_run(): void
    {
        $handled_by_global_handler = false;
        set_error_handler(function () use (&$handled_by_global_handler): bool {
            $handled_by_global_handler = true;

            return true;
        });

        try {
            $kernel = new Kernel(
                $this->newContainer(),
                Environment::testing(),
                $this->directories
            );

            $kernel->boot();

            /** @var HttpKernel $http_kernel */
            $http_kernel = $kernel->container()->make(HttpKernel::class);

            $request = new ServerRequest('GET', '/trigger-notice');

            $response = $http_kernel->handle(Request::fromPsr($request));

            $this->assertSame(500, $response->getStatusCode());
            $this->assertFalse(
                $handled_by_global_handler,
                'the error was handled by the global exception handler, not the kernel.'
            );

            trigger_error('foo', E_USER_NOTICE);

            /**
             * @psalm-suppress DocblockTypeContradiction
             */
            $this->assertTrue(
                $handled_by_global_handler,
                'the error was handled by the global exception handler, not the kernel.'
            );
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @test
     */
    public function deprecations_are_not_converted_to_exceptions(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );

        $kernel->boot();

        /** @var HttpKernel $http_kernel */
        $http_kernel = $kernel->container()->make(HttpKernel::class);

        $request = new ServerRequest('GET', '/trigger-deprecation');

        $response = $http_kernel->handle(Request::fromPsr($request));

        $this->assertSame(200, $response->getStatusCode());

        /** @var TestLogger $test_logger */
        $test_logger = $kernel->container()->make(TestLogger::class);
        $this->assertTrue(
            $test_logger->hasInfoThatContains('PHP Deprecated')
        );
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}

class Transformer1 implements ExceptionTransformer
{
    public function transform(Throwable $e): Throwable
    {
        if ($e instanceof LogicException) {
            return new HttpException(401, 'test');
        }

        if ('error1' === $e->getMessage()) {
            return new HttpException(403, 'custom1');
        }

        return $e;
    }
}

class Transformer2 implements ExceptionTransformer
{
    public function transform(Throwable $e): Throwable
    {
        if ('error1' === $e->getMessage()) {
            return new HttpException(404, 'custom2');
        }

        return $e;
    }
}

class PathLogContext implements RequestLogContext
{
    public function add(array $context, ExceptionInformation $information): array
    {
        $context['path'] = $information->serverRequest()->getUri()->getPath();

        return $context;
    }
}

class QueryStringLogContext implements RequestLogContext
{
    public function add(array $context, ExceptionInformation $information): array
    {
        $context['query_string'] = $information->serverRequest()->getUri()->getQuery();

        return $context;
    }
}

class CustomHtmlDisplayer implements ExceptionDisplayer
{
    public function display(ExceptionInformation $exception_information): string
    {
        return 'foobar';
    }

    public function supportedContentType(): string
    {
        return 'text/html';
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        return true;
    }
}
