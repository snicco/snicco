<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\unit;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\HttpRouting\Middleware\SimpleTemplating;
use Snicco\Bundle\HttpRouting\Tests\fixtures\RoutingBundleTestController;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;
use function is_file;
use function unlink;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 *
 * @internal
 */
final class RoutingFunctionalityTest extends TestCase
{
    use BundleTestHelpers;

    private string $expected_route_cache_file;

    private string $cache_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        $this->expected_route_cache_file = $this->directories->cacheDir() . '/prod.routes-generated.php';
        if (is_file($this->expected_route_cache_file)) {
            unlink($this->expected_route_cache_file);
        }
    }

    /**
     * @test
     */
    public function routes_are_loaded_at_runtime_in_non_prod_staging_environment(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class,
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(RoutingBundleTestController::class, (string) $response->getBody());
        $this->assertFalse(is_file($this->expected_route_cache_file));
    }

    /**
     * @test
     */
    public function routes_are_cached_in_production(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::prod(),
            $this->directories
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class,
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(RoutingBundleTestController::class, (string) $response->getBody());
        $this->assertTrue(is_file($this->expected_route_cache_file));
        $this->assertTrue(is_file($this->directories->cacheDir() . '/prod.middleware-map-generated.php'));
    }

    /**
     * @test
     */
    public function redirect_routes_work(): void
    {
        $container = new PimpleContainerAdapter();
        $kernel = new Kernel(
            $container,
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/foo');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class,
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function delegated_responses_work(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/delegate');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class,
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertInstanceOf(DelegatedResponse::class, $response);
    }

    /**
     * @test
     */
    public function view_responses_are_transformed(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/view');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                SimpleTemplating::class,
                RoutingMiddleware::class,
                RouteRunner::class,
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertNotInstanceOf(ViewResponse::class, $response);
        $this->assertSame('Hello Calvin', (string) $response->getBody());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
    }

    /**
     * @test
     */
    public function non_view_responses_are_not_affected(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/foo');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                SimpleTemplating::class,
                RoutingMiddleware::class,
                RouteRunner::class,
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('', (string) $response->getBody());
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}
