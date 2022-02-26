<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;
use Snicco\Bundle\HttpRouting\Tests\fixtures\Middleware\MiddlewareThree;
use Snicco\Bundle\HttpRouting\Tests\fixtures\RoutingBundleTestController;
use Snicco\Bundle\Testing\BundleTestHelpers;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;
use function is_file;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 */
final class MiddlewareCacheTest extends TestCase
{

    use BundleTestHelpers;

    /**
     * @test
     */
    public function route_middleware_is_not_cached_in_non_production(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfiguration(function (WritableConfig $config) {
            $config->set('middleware', [
                MiddlewareOption::ALWAYS_RUN => [
                    RoutingConfigurator::FRONTEND_MIDDLEWARE
                ],
                MiddlewareOption::ALIASES => [],
                MiddlewareOption::GROUPS => ['frontend' => [MiddlewareThree::class]],
                MiddlewareOption::PRIORITY_LIST => [],
            ]);
        });

        $this->assertFalse(is_file($this->directories->cacheDir() . '/prod.middleware-map-generated.php'));

        $kernel->boot();

        $kernel->container()->make(RouteRunner::class);

        $this->assertFalse(is_file($this->directories->cacheDir() . '/prod.middleware-map-generated.php'));
    }

    /**
     * @test
     */
    public function route_middleware_is_cached_in_production(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::prod(),
            $this->directories
        );

        $kernel->afterConfiguration(function (WritableConfig $config) {
            $config->set('middleware', [
                MiddlewareOption::ALWAYS_RUN => [
                    RoutingConfigurator::FRONTEND_MIDDLEWARE
                ],
                MiddlewareOption::ALIASES => [],
                MiddlewareOption::GROUPS => ['frontend' => [MiddlewareThree::class]],
                MiddlewareOption::PRIORITY_LIST => [],
            ]);
        });

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->cacheDir() . '/prod.middleware-map-generated.php'));

        $kernel->container()->make(RouteRunner::class);

        $this->assertTrue(is_file($this->directories->cacheDir() . '/prod.middleware-map-generated.php'));

        $new_kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::prod(),
            $this->directories
        );

        $new_kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $new_kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/middleware1');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class
            ])->then(function () {
                throw new RuntimeException('pipeline exhausted');
            });

        $this->assertSame(
            RoutingBundleTestController::class . ':middleware_one:middleware_three',
            (string)$response->getBody()
        );

        $request = new ServerRequest('GET', '/middleware2');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class
            ])->then(function () {
                throw new RuntimeException('pipeline exhausted');
            });

        $this->assertSame(
            RoutingBundleTestController::class . ':middleware_two:middleware_one:middleware_three',
            (string)$response->getBody()
        );

        // Request with no routes
        $request = new ServerRequest('GET', '/bogus');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([
                RoutingMiddleware::class,
                RouteRunner::class
            ])->then(function () {
                throw new RuntimeException('pipeline exhausted');
            });

        $this->assertSame(
            ':middleware_three',
            (string)$response->getBody()
        );
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}