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

}

class MiddlewareController extends AbstractController
{

    public function __construct()
    {
        $this->middleware(FoobarMiddleware::class);
        $this->middleware(FooMiddleware::class)->except(['bar', 'all']);
        $this->middleware(BarMiddleware::class)->only('bar');
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