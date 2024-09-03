<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\unit;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\HttpRouting\Middleware\SimpleTemplating;
use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;
use Snicco\Bundle\HttpRouting\Tests\fixtures\Middleware\MiddlewareThree;
use Snicco\Bundle\HttpRouting\Tests\fixtures\RoutingBundleTestController;
use Snicco\Bundle\HttpRouting\Tests\fixtures\TestCustomRouteLoader;
use Snicco\Bundle\HttpRouting\Tests\fixtures\TestCustomRouteLoadingOptions;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\Kernel\Cache\NullCache;
use Snicco\Component\Kernel\Configuration\WritableConfig;
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

    private string $expected_middleware_cache_file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        $this->expected_route_cache_file = $this->directories->cacheDir() . '/snicco_http_routing_bundle.routes.php';
        $this->expected_middleware_cache_file = $this->directories->cacheDir() . '/snicco_http_routing_bundle.middleware.php';
        unset($_SERVER['TEST_NO_LOAD_ROUTES'], $_SERVER['TEST_NO_ADD_ROUTES_MIDDLEWARE']);
        if (is_file($this->expected_route_cache_file)) {
            unlink($this->expected_route_cache_file);
        }
    }

    protected function tearDown(): void
    {
        unset($_SERVER['TEST_NO_LOAD_ROUTES'], $_SERVER['TEST_NO_ADD_ROUTES_MIDDLEWARE']);
        $this->bundle_test->tearDownDirectories();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function routes_are_loaded_at_runtime_in_non_prod_staging_environment(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories);

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(RoutingBundleTestController::class, (string) $response->getBody());
        $this->assertFalse(is_file($this->expected_route_cache_file));
        $this->assertFalse(is_file($this->expected_middleware_cache_file));

        $_SERVER['TEST_NO_LOAD_ROUTES'] = 1;

        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories);
        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        // Routes are not cached and the ENV var prevented loading in the fixtures.
        $this->assertInstanceOf(DelegatedResponse::class, $response);
    }

    /**
     * @test
     */
    public function routes_are_cached_in_production(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::prod(), $this->directories);
        $kernel->boot();
        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(RoutingBundleTestController::class, (string) $response->getBody());
        $this->assertTrue(is_file($this->expected_route_cache_file));
        $this->assertTrue(is_file($this->expected_middleware_cache_file));

        $_SERVER['TEST_NO_LOAD_ROUTES'] = 1;

        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::prod(), $this->directories);
        $kernel->boot();
        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        // Routes are cached and not loaded again.
        $this->assertSame(RoutingBundleTestController::class, (string) $response->getBody());
    }

    /**
     * @test
     */
    public function routes_are_not_cached_in_production_if_kernel_uses_null_cache(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::prod(), $this->directories, new NullCache());
        $kernel->boot();
        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(RoutingBundleTestController::class, (string) $response->getBody());
        $this->assertFalse(is_file($this->expected_route_cache_file));
        $this->assertFalse(is_file($this->expected_middleware_cache_file));

        $_SERVER['TEST_NO_LOAD_ROUTES'] = 1;

        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::prod(),
            $this->directories,
            new NullCache()
        );

        $kernel->boot();
        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        // Routes are not cached and the ENV var prevented loading in the fixtures.
        $this->assertInstanceOf(DelegatedResponse::class, $response);
    }

    /**
     * @test
     */
    public function route_middleware_is_not_cached_in_non_production(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('middleware', [
                MiddlewareOption::ALWAYS_RUN => [RoutingConfigurator::FRONTEND_MIDDLEWARE],
                MiddlewareOption::ALIASES => [],
                MiddlewareOption::GROUPS => [
                    'frontend' => [MiddlewareThree::class],
                ],
                MiddlewareOption::PRIORITY_LIST => [],
            ]);
        });

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('pipeline exhausted');
            });

        $this->assertSame(
            RoutingBundleTestController::class . ':middleware_three',
            (string) $response->getBody()
        );

        $new_kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories);
        $new_kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $new_kernel->container()
            ->make(MiddlewarePipeline::class);

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('pipeline exhausted');
            });

        $response_body = (string) $response->getBody();
        $this->assertStringNotContainsString(
            'middleware_three',
            $response_body,
            'The middleware should not have been cached here, :middleware_not_there'
        );
        $this->assertSame(
            RoutingBundleTestController::class,
            $response_body,
        );
    }

    /**
     * @test
     */
    public function route_middleware_is_cached_in_production(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::prod(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('middleware', [
                MiddlewareOption::ALWAYS_RUN => [RoutingConfigurator::FRONTEND_MIDDLEWARE],
                MiddlewareOption::ALIASES => [],
                MiddlewareOption::GROUPS => [
                    'frontend' => [MiddlewareThree::class],
                ],
                MiddlewareOption::PRIORITY_LIST => [],
            ]);
        });

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('pipeline exhausted');
            });

        $this->assertSame(
            RoutingBundleTestController::class . ':middleware_three',
            (string) $response->getBody()
        );

        $new_kernel = new Kernel(new PimpleContainerAdapter(), Environment::prod(), $this->directories);
        $new_kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $new_kernel->container()
            ->make(MiddlewarePipeline::class);

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('pipeline exhausted');
            });

        $this->assertSame(
            RoutingBundleTestController::class . ':middleware_three',
            (string) $response->getBody()
        );
    }

    /**
     * @test
     */
    public function route_middleware_is_not_cached_if_kernel_uses_null_cache(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::prod(), $this->directories, new NullCache());

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('middleware', [
                MiddlewareOption::ALWAYS_RUN => [RoutingConfigurator::FRONTEND_MIDDLEWARE],
                MiddlewareOption::ALIASES => [],
                MiddlewareOption::GROUPS => [
                    'frontend' => [MiddlewareThree::class],
                ],
                MiddlewareOption::PRIORITY_LIST => [],
            ]);
        });

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/frontend');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('pipeline exhausted');
            });

        $this->assertSame(
            RoutingBundleTestController::class . ':middleware_three',
            (string) $response->getBody()
        );

        $new_kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::prod(),
            $this->directories,
            new NullCache()
        );
        $new_kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $new_kernel->container()
            ->make(MiddlewarePipeline::class);

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('pipeline exhausted');
            });

        $response_body = (string) $response->getBody();
        $this->assertStringNotContainsString(
            'middleware_three',
            $response_body,
            'The middleware should not have been cached here, :middleware_not_there'
        );
        $this->assertSame(
            RoutingBundleTestController::class,
            $response_body,
        );
    }

    /**
     * @test
     */
    public function redirect_routes_work(): void
    {
        $container = new PimpleContainerAdapter();
        $kernel = new Kernel($container, Environment::dev(), $this->directories);

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/foo');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function delegated_responses_work(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories);

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/delegate');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertInstanceOf(DelegatedResponse::class, $response);
    }

    /**
     * @test
     */
    public function view_responses_are_transformed(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories);

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/view');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([SimpleTemplating::class, RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
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
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories);

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/foo');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([SimpleTemplating::class, RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function custom_route_loading_options_can_be_used(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories);

        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()
                ->instance(RouteLoadingOptions::class, new TestCustomRouteLoadingOptions());
        });

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/custom-prefix/view');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([SimpleTemplating::class, RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertNotInstanceOf(ViewResponse::class, $response);
        $this->assertSame('Hello Calvin', (string) $response->getBody());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
    }

    /**
     * @test
     */
    public function a_custom_route_loader_can_be_used(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories);

        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()
                ->instance(RouteLoader::class, new TestCustomRouteLoader());
        });

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/foo-custom');

        $response = $pipeline
            ->send(Request::fromPsr($request))
            ->through([SimpleTemplating::class, RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(RoutingBundleTestController::class, (string) $response->getBody());
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}
