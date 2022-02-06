<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

final class IncludingRoutesTest extends HttpRunnerTestCase
{

    /**
     * @test
     */
    public function test_exception_if_no_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string or a closure');

        $this->routeConfigurator()->include(1);
    }

    /**
     * @test
     */
    public function test_exception_if_unreadable_file(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not readable');

        $this->routeConfigurator()->include($this->routes_dir . '/bogus.php');
    }

    /**
     * @test
     */
    public function test_exception_if_no_closure_returned(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('has to return a closure');

        $this->routeConfigurator()->include($this->routes_dir . '/_no_closure.php');
    }

    /**
     * @test
     */
    public function routes_can_be_included_as_a_string(): void
    {
        $this->withMiddlewareAlias(['partial' => FooMiddleware::class]);
        $this->routeConfigurator()->include($this->routes_dir . '/_partial.php');

        $this->assertResponseBody(
            RoutingTestController::static . ':foo_middleware',
            $this->frontendRequest(RouteLoaderTest::PARTIAL_PATH)
        );
    }

    /**
     * @test
     */
    public function routes_can_be_included_as_a_closure(): void
    {
        $this->withMiddlewareAlias(['partial' => FooMiddleware::class]);

        $closure = require $this->routes_dir . '/_partial.php';

        $this->routeConfigurator()->include($closure);

        $this->assertResponseBody(
            RoutingTestController::static . ':foo_middleware',
            $this->frontendRequest(RouteLoaderTest::PARTIAL_PATH)
        );
    }

}