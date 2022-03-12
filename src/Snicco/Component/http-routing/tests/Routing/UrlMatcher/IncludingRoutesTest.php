<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use Closure;
use InvalidArgumentException;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;
use Snicco\Component\HttpRouting\Tests\Routing\RouteLoader\PHPFileRouteLoaderTest;

/**
 * @internal
 */
final class IncludingRoutesTest extends HttpRunnerTestCase
{
    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exception_if_no_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string or a closure');

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->include(1);
        });
    }

    /**
     * @test
     */
    public function test_exception_if_unreadable_file(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not readable');

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->include($this->routes_dir . '/bogus.php');
        });
    }

    /**
     * @test
     */
    public function test_exception_if_no_closure_returned(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('has to return a closure');

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->include($this->routes_dir . '/_no_closure.php');
        });
    }

    /**
     * @test
     */
    public function routes_can_be_included_as_a_file(): void
    {
        $this->withMiddlewareAlias([
            'partial' => FooMiddleware::class,
        ]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->include($this->routes_dir . '/_partial.php');
        });

        $this->assertResponseBody(
            RoutingTestController::static . ':foo_middleware',
            $this->frontendRequest(PHPFileRouteLoaderTest::PARTIAL_PATH)
        );
    }

    /**
     * @test
     * @psalm-suppress UnresolvableInclude
     */
    public function routes_can_be_included_as_a_closure(): void
    {
        $this->withMiddlewareAlias([
            'partial' => FooMiddleware::class,
        ]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            /** @var Closure(WebRoutingConfigurator):void $closure */
            $closure = require $this->routes_dir . '/_partial.php';
            $configurator->include($closure);
        });

        $this->assertResponseBody(
            RoutingTestController::static . ':foo_middleware',
            $this->frontendRequest(PHPFileRouteLoaderTest::PARTIAL_PATH)
        );
    }
}
