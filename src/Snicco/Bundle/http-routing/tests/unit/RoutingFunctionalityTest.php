<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\HttpRouting\SimpleTemplatingMiddleware;
use Snicco\Bundle\HttpRouting\Tests\unit\fixtures\RoutingBundleTestController;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function is_file;
use function unlink;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 */
final class RoutingFunctionalityTest extends TestCase
{

    private Directories $dirs;
    private string $expected_cache_file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->expected_cache_file = __DIR__ . '/fixtures/var/cache/prod.routes-generated.php';
        if (is_file($this->expected_cache_file)) {
            unlink($this->expected_cache_file);
        }
        $this->dirs = Directories::fromDefaults(__DIR__ . '/fixtures');
    }

    protected function tearDown(): void
    {
        if (is_file($this->expected_cache_file)) {
            unlink($this->expected_cache_file);
        }
        if (is_file(__DIR__ . '/fixtures/var/cache/prod.middleware-map-generated.php')) {
            unlink(__DIR__ . '/fixtures/var/cache/prod.middleware-map-generated.php');
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function routes_are_loaded_at_runtime_in_non_prod_staging_environment(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::dev(),
            $this->dirs
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(RoutingBundleTestController::class, (string)$response->getBody());
        $this->assertFalse(is_file($this->expected_cache_file));
    }

    /**
     * @test
     */
    public function routes_are_cached_in_production(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::prod(),
            $this->dirs
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(RoutingBundleTestController::class, (string)$response->getBody());
        $this->assertTrue(is_file($this->expected_cache_file));
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
            $this->dirs
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/foo');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class
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
            $this->dirs
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/delegate');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class
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
            $this->dirs
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/view');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                SimpleTemplatingMiddleware::class,
                RoutingMiddleware::class,
                RouteRunner::class
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertNotInstanceOf(ViewResponse::class, $response);
        $this->assertSame('Hello Calvin', (string)$response->getBody());
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
            $this->dirs
        );

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/foo');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                SimpleTemplatingMiddleware::class,
                RoutingMiddleware::class,
                RouteRunner::class
            ])->then(function () {
                throw new RuntimeException('no routing performed');
            });

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('', (string)$response->getBody());
    }


}