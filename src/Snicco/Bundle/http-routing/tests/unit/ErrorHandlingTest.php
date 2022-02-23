<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\unit;

use LogicException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use RuntimeException;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\HttpRouting\RoutingOption;
use Snicco\Bundle\HttpRouting\StdErrLogger;
use Snicco\Bundle\Testing\BootsKernelForBundleTest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;
use Snicco\Component\Psr7ErrorHandler\Log\RequestLogContext;
use Throwable;
use TypeError;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 */
final class ErrorHandlingTest extends TestCase
{

    use BootsKernelForBundleTest;

    /**
     * @test
     */
    public function exceptions_are_handled_by_default_during_routing(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            Directories::fromDefaults(__DIR__ . '/fixtures')
        );

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $response = $pipeline
            ->send(Request::fromPsr(new ServerRequest('GET', '/')))
            ->through([

            ])
            ->then(function () {
                throw new RuntimeException('secret error in routing.');
            });

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('secret error in routing', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function a_test_logger_is_used_in_testing_environments(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::testing(),
            Directories::fromDefaults(__DIR__ . '/fixtures')
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
    }

    /**
     * @test
     */
    public function the_test_logger_is_not_bound_in_non_testing_env(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::prod(),
            Directories::fromDefaults(__DIR__ . '/fixtures')
        );

        $kernel->boot();

        $this->assertNotBound(TestLogger::class, $kernel);
    }

    /**
     * @test
     */
    public function request_log_context_can_be_added(): void
    {
        $kernel = $this->bootWithExtraConfig([
            'routing' => [
                RoutingOption::EXCEPTION_REQUEST_CONTEXT => [
                    PathLogContext::class,
                    QueryStringLogContext::class
                ]
            ]
        ], Directories::fromDefaults(__DIR__ . '/fixtures'));

        /**
         * @var MiddlewarePipeline $pipeline
         */
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
        $kernel = $this->bootWithExtraConfig([
            'routing' => [
                RoutingOption::EXCEPTION_TRANSFORMERS => [
                    Transformer2::class,
                    Transformer1::class,
                ]
            ]
        ], Directories::fromDefaults(__DIR__ . '/fixtures'));

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
        $kernel = $this->bootWithExtraConfig([
            'routing' => [
                RoutingOption::EXCEPTION_DISPLAYERS => [
                    CustomHtmlDisplayer::class,
                ]
            ]
        ], Directories::fromDefaults(__DIR__ . '/fixtures'));

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

        $this->assertSame('foobar', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function custom_log_levels_can_be_used(): void
    {
        $kernel = $this->bootWithExtraConfig([
            'routing' => [
                RoutingOption::EXCEPTION_LOG_LEVELS => [
                    TypeError::class => LogLevel::WARNING
                ]
            ]
        ], Directories::fromDefaults(__DIR__ . '/fixtures'));

        /**
         * @var MiddlewarePipeline $pipeline
         */
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
    public function the_production_the_logger_is_bound_if_not_already_set_in_the_container(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::prod(),
            Directories::fromDefaults(__DIR__ . '/fixtures')
        );
        $kernel->boot();

        $this->assertInstanceOf(StdErrLogger::class, $kernel->container()->make(LoggerInterface::class));

        $container = $this->container();
        $container[LoggerInterface::class] = fn() => new NullLogger();

        $kernel = new Kernel(
            $container,
            Environment::dev(),
            Directories::fromDefaults(__DIR__ . '/fixtures')
        );
        $kernel->boot();

        $this->assertInstanceOf(NullLogger::class, $kernel->container()->make(LoggerInterface::class));
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

class Transformer1 implements ExceptionTransformer
{

    public function transform(Throwable $e): Throwable
    {
        if ($e instanceof LogicException) {
            return new HttpException(401, 'test');
        }

        if ($e->getMessage() === 'error1') {
            return new HttpException(403, 'custom1');
        }
        return $e;
    }
}

class Transformer2 implements ExceptionTransformer
{


    public function transform(Throwable $e): Throwable
    {
        if ($e->getMessage() === 'error1') {
            return new HttpException(404, 'custom2');
        }
        return $e;
    }
}

class PathLogContext implements RequestLogContext
{

    public function add(array $context, RequestInterface $request, ExceptionInformation $information): array
    {
        $context['path'] = $request->getUri()->getPath();
        return $context;
    }
}

class QueryStringLogContext implements RequestLogContext
{

    public function add(array $context, RequestInterface $request, ExceptionInformation $information): array
    {
        $context['query_string'] = $request->getUri()->getQuery();
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