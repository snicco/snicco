<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Routing;

use Snicco\Component\HttpRouting\AbstractController;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FoobarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

final class ControllerMiddlewareTest extends HttpRunnerTestCase
{

    /**
     * @test
     */
    public function controller_middleware_can_apply_to_all_methods(): void
    {
        $this->routeConfigurator()->get('all', '/all', [MiddlewareController::class, 'all']);

        $response = $this->runKernel($this->frontendRequest('/all'));

        $response->assertSeeText('all:foobar_middleware');
    }

    /**
     * @test
     */
    public function controller_middleware_can_apply_to_a_single_method(): void
    {
        $this->routeConfigurator()->get('all', '/bar', [MiddlewareController::class, 'bar']);

        $response = $this->runKernel($this->frontendRequest('/bar'));

        $response->assertSeeText('bar:bar_middleware:foobar_middleware');
    }

    /**
     * @test
     */
    public function controller_middleware_can_be_applied_to_all_but_some_methods(): void
    {
        $this->routeConfigurator()->get('baz', '/baz', [MiddlewareController::class, 'baz']);
        $response = $this->runKernel($this->frontendRequest('/baz'));
        $response->assertSeeText('baz:foo_middleware:foobar_middleware');

        $this->routeConfigurator()->get('foo', '/foo', [MiddlewareController::class, 'foo']);
        $response = $this->runKernel($this->frontendRequest('/foo'));
        $response->assertSeeText('foo:foo_middleware:foobar_middleware');
    }

    /**
     * @test
     */
    public function middleware_can_be_added_as_an_array(): void
    {
        $this->routeConfigurator()->get('handle', '/handle', [ArrayMiddlewareController::class, 'handle']);
        $response = $this->runKernel($this->frontendRequest('/handle'));
        $response->assertSeeText('handle:bar_middleware:foo_middleware');
    }
}

class ArrayMiddlewareController extends AbstractController
{

    public function __construct()
    {
        $this->addMiddleware([FooMiddleware::class, BarMiddleware::class]);
    }

    public function handle(): string
    {
        return 'handle';
    }

}

class MiddlewareController extends AbstractController
{

    public function __construct()
    {
        $this->addMiddleware(FoobarMiddleware::class);
        $this->addMiddleware(FooMiddleware::class)->exceptForMethods(['bar', 'all']);
        $this->addMiddleware(BarMiddleware::class)->toMethods('bar');
    }

    public function foo(): string
    {
        return 'foo';
    }

    public function bar(): string
    {
        return 'bar';
    }

    public function baz(): string
    {
        return 'baz';
    }

    public function all(): string
    {
        return 'all';
    }

}