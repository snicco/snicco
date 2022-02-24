<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\HttpRouting\Tests\unit\fixtures\Middleware\MiddlewareThree;
use Snicco\Bundle\HttpRouting\Tests\unit\fixtures\RoutingBundleTestController;
use Snicco\Bundle\Testing\BootsKernelForBundleTest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function is_file;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 */
final class MiddlewareCacheTest extends TestCase
{
    use BootsKernelForBundleTest;

    private Directories $directories;
    private string $base_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = __DIR__ . '/fixtures/tmp';
        $this->directories = $this->setUpDirectories($this->base_dir);
    }

    protected function tearDown(): void
    {
        $this->tearDownDirectories($this->base_dir);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function route_middleware_is_not_cached_in_non_production(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [__DIR__ . '/fixtures/routes-with-middleware'],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::USE_HTTPS => true,
                    RoutingOption::HTTPS_PORT => 443,
                    RoutingOption::HTTP_PORT => 80,
                ],
                'middleware' => [
                    MiddlewareOption::ALWAYS_RUN => [
                        RoutingConfigurator::FRONTEND_MIDDLEWARE
                    ],
                    MiddlewareOption::ALIASES => [],
                    MiddlewareOption::GROUPS => ['frontend' => [MiddlewareThree::class]],
                    MiddlewareOption::PRIORITY_LIST => [],
                ]
            ]
            , $this->directories, Environment::dev());

        $this->assertFalse(is_file($this->directories->cacheDir() . '/prod.middleware-map-generated.php'));

        $kernel->container()->make(RouteRunner::class);

        $this->assertFalse(is_file($this->directories->cacheDir() . '/prod.middleware-map-generated.php'));
    }

    /**
     * @test
     */
    public function route_middleware_is_cached_in_production(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [__DIR__ . '/fixtures/routes-with-middleware'],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp-login',
                    RoutingOption::API_PREFIX => '/test',

                    RoutingOption::USE_HTTPS => true,
                    RoutingOption::HTTPS_PORT => 443,
                    RoutingOption::HTTP_PORT => 80,
                ],
                'middleware' => [
                    MiddlewareOption::ALWAYS_RUN => [
                        RoutingConfigurator::FRONTEND_MIDDLEWARE
                    ],
                    MiddlewareOption::ALIASES => [],
                    MiddlewareOption::GROUPS => ['frontend' => [MiddlewareThree::class]],
                    MiddlewareOption::PRIORITY_LIST => [],
                ]
            ]
            , $this->directories, Environment::prod());

        $this->assertFalse(is_file($this->directories->cacheDir() . '/prod.middleware-map-generated.php'));

        $kernel->container()->make(RouteRunner::class);

        $this->assertTrue(is_file($this->directories->cacheDir() . '/prod.middleware-map-generated.php'));

        $new_kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [__DIR__ . '/fixtures/routes-with-middleware'],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::USE_HTTPS => true,
                    RoutingOption::HTTPS_PORT => 443,
                    RoutingOption::HTTP_PORT => 80,
                ],
                'middleware' => [
                    MiddlewareOption::ALWAYS_RUN => [
                        RoutingConfigurator::FRONTEND_MIDDLEWARE
                    ],
                    MiddlewareOption::ALIASES => [],
                    MiddlewareOption::GROUPS => [],
                    MiddlewareOption::PRIORITY_LIST => [],
                ]
            ]
            , $this->directories, Environment::prod());

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $new_kernel->container()->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/web1');

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

        $request = new ServerRequest('GET', '/web2');

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

    protected function bundles(): array
    {
        return [
            Environment::ALL => [
                HttpRoutingBundle::class,
                BetterWPHooksBundle::class
            ]
        ];
    }
}