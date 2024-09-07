<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Controller\ControllerMiddleware;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FoobarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class ControllerMiddlewareTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function controller_middleware_can_apply_to_all_methods(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('all', '/all', [MiddlewareController::class, 'all']);
        });

        $response = $this->runNewPipeline($this->frontendRequest('/all'));

        $response->assertSeeText('all:foobar_middleware');
    }

    /**
     * @test
     */
    public function controller_middleware_can_apply_to_a_single_method(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('all', '/bar', [MiddlewareController::class, 'bar']);
        });

        $response = $this->runNewPipeline($this->frontendRequest('/bar'));

        $response->assertSeeText('bar:bar_middleware:foobar_middleware');
    }

    /**
     * @test
     */
    public function controller_middleware_can_be_applied_to_all_but_some_methods(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('baz', '/baz', [MiddlewareController::class, 'baz']);
            $configurator->get('foo', '/foo', [MiddlewareController::class, 'foo']);
        });
        $response = $this->runNewPipeline($this->frontendRequest('/baz'));
        $response->assertSeeText('baz:foo_middleware:foobar_middleware');

        $response = $this->runNewPipeline($this->frontendRequest('/foo'));
        $response->assertSeeText('foo:foo_middleware:foobar_middleware');
    }

    /**
     * @test
     */
    public function middleware_can_be_added_as_an_array(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('handle', '/handle', [ArrayMiddlewareController::class, 'handle']);
        });
        $response = $this->runNewPipeline($this->frontendRequest('/handle'));
        $response->assertSeeText('handle:bar_middleware:foo_middleware');
    }
}

final class ArrayMiddlewareController extends Controller
{
    public static function middleware()
    {
        return [
            new ControllerMiddleware([FooMiddleware::class, BarMiddleware::class]),
        ];
    }

    public function handle(): string
    {
        return 'handle';
    }
}

final class MiddlewareController extends Controller
{
    public static function middleware()
    {
        yield new ControllerMiddleware(FoobarMiddleware::class);

        yield (new ControllerMiddleware(FooMiddleware::class))->only(['bar', 'all']);

        yield (new ControllerMiddleware(BarMiddleware::class))->except('bar');
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
