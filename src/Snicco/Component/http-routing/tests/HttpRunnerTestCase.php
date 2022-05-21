<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests;

use Closure;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Controller\DelegateResponseController;
use Snicco\Component\HttpRouting\Controller\RedirectController;
use Snicco\Component\HttpRouting\Controller\ViewController;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\MiddlewareResolver;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Cache\NullCache;
use Snicco\Component\HttpRouting\Routing\Cache\RouteCache;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoader;
use Snicco\Component\HttpRouting\Routing\Router;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Testing\AssertableResponse;
use Snicco\Component\HttpRouting\Testing\CreatesPsrRequests;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BazMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FoobarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\NullErrorHandler;
use Snicco\Component\HttpRouting\Tests\helpers\CreateHttpErrorHandler;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

use function array_merge;
use function call_user_func;

/**
 * @interal
 */
abstract class HttpRunnerTestCase extends TestCase
{
    use CreateTestPsr17Factories;
    use CreatesPsrRequests;
    use CreateHttpErrorHandler;

    /**
     * @var string
     */
    public const CONTROLLER_NAMESPACE = 'Snicco\\Component\\HttpRouting\\Tests\\fixtures\\Controller';

    protected string $app_domain = 'foobar.com';

    protected string $routes_dir;

    protected Container $pimple;

    protected ContainerInterface $psr_container;

    private Router $routing;

    /**
     * @var list<class-string<MiddlewareInterface>>
     */
    private array $middleware_priority = [];

    /**
     * @var array<string,string[]>
     */
    private array $middleware_groups = [];

    /**
     * @var array<string,class-string<MiddlewareInterface>>
     */
    private array $middleware_aliases = [
        'foo' => FooMiddleware::class,
        'bar' => BarMiddleware::class,
        'baz' => BazMiddleware::class,
        'foobar' => FoobarMiddleware::class,
    ];

    /**
     * @var array<
     *     RoutingConfigurator::FRONTEND_MIDDLEWARE |
     *     RoutingConfigurator::ADMIN_MIDDLEWARE |
     *     RoutingConfigurator::API_MIDDLEWARE |
     *     RoutingConfigurator::GLOBAL_MIDDLEWARE
     * > $group_names
     */
    private array $always_run = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->pimple = new Container();
        $this->psr_container = new \Pimple\Psr11\Container($this->pimple);

        // internal controllers
        $this->pimple[DelegateResponseController::class] = fn (): DelegateResponseController => new DelegateResponseController();
        $this->pimple[ViewController::class] = fn (): ViewController => new ViewController();
        $this->pimple[RedirectController::class] = fn (): RedirectController => new RedirectController();

        // TestController
        $controller = new RoutingTestController();
        $this->pimple[RoutingTestController::class] = fn (): RoutingTestController => $controller;

        $this->routes_dir = __DIR__ . '/fixtures/routes';
    }

    final protected function assertEmptyBody(Request $request): void
    {
        $this->assertResponseBody('', $request);
    }

    final protected function assertResponseBody(string $expected, Request $request): void
    {
        $response = $this->runNewPipeline($request);
        $this->assertSame(
            $expected,
            $b = $response->body(),
            "Expected response body [{$expected}] for path [{$request->path()}].\nGot [{$b}]."
        );
    }

    final protected function runNewPipeline(Request $request): AssertableResponse
    {
        if (! isset($this->routing)) {
            $this->routing = $this->newRoutingFacade();
        }

        $pipeline = $this->newPipeline();
        $response = $pipeline->send($request)
            ->then(function () {
                throw new RuntimeException('Middleware pipeline exhausted.');
            });

        return new AssertableResponse($response);
    }

    /**
     * @param array<string,class-string<MiddlewareInterface>> $aliases
     */
    final protected function withMiddlewareAlias(array $aliases): void
    {
        $this->middleware_aliases = array_merge($this->middleware_aliases, $aliases);
    }

    /**
     * @param string[] $middleware
     */
    final protected function withGlobalMiddleware(array $middleware): void
    {
        $this->middleware_groups[RoutingConfigurator::GLOBAL_MIDDLEWARE] = $middleware;
    }

    /**
     * @param array<string,array<string>> $middlewares
     */
    final protected function withMiddlewareGroups(array $middlewares): void
    {
        $this->middleware_groups = array_merge($this->middleware_groups, $middlewares);
    }

    /**
     * @param list<class-string<MiddlewareInterface>> $priority
     */
    final protected function withMiddlewarePriority(array $priority): void
    {
        $this->middleware_priority = $priority;
    }

    final protected function generator(UrlGenerationContext $context = null): UrlGenerator
    {
        $this->routing = $this->newRoutingFacade(null, null, $context);

        return $this->routing->urlGenerator();
    }

    /**
     * @param Closure(WebRoutingConfigurator) $loader
     */
    final protected function webRouting(
        Closure $loader,
        ?RouteCache $cache = null,
        ?UrlGenerationContext $context = null
    ): Router {
        $on_the_fly_loader = new class($loader) implements RouteLoader {
            private Closure $loader;

            public function __construct(Closure $loader)
            {
                $this->loader = $loader;
            }

            public function loadWebRoutes(WebRoutingConfigurator $configurator): void
            {
                call_user_func($this->loader, $configurator);
            }

            public function loadAdminRoutes(AdminRoutingConfigurator $configurator): void
            {
            }
        };

        return $this->newRoutingFacade($on_the_fly_loader, $cache, $context);
    }

    /**
     * @param Closure(AdminRoutingConfigurator) $loader
     */
    final protected function adminRouting(
        Closure $loader,
        ?RouteCache $cache = null,
        ?UrlGenerationContext $context = null
    ): Router {
        $on_the_fly_loader = new class($loader) implements RouteLoader {
            private Closure $loader;

            public function __construct(Closure $loader)
            {
                $this->loader = $loader;
            }

            public function loadWebRoutes(WebRoutingConfigurator $configurator): void
            {
            }

            public function loadAdminRoutes(AdminRoutingConfigurator $configurator): void
            {
                call_user_func($this->loader, $configurator);
            }
        };

        return $this->newRoutingFacade($on_the_fly_loader, $cache, $context);
    }

    /**
     * @param array<
     *     RoutingConfigurator::FRONTEND_MIDDLEWARE |
     *     RoutingConfigurator::ADMIN_MIDDLEWARE |
     *     RoutingConfigurator::API_MIDDLEWARE |
     *     RoutingConfigurator::GLOBAL_MIDDLEWARE
     * > $group_names
     */
    protected function alwaysRun(array $group_names): void
    {
        $this->always_run = $group_names;
    }

    protected function newRoutingFacade(
        RouteLoader $loader = null,
        ?RouteCache $cache = null,
        UrlGenerationContext $context = null
    ): Router {
        $routing = new Router(
            $context ?: new UrlGenerationContext($this->app_domain),
            $loader ?: $this->nullLoader(),
            $cache ?: new NullCache(),
            WPAdminArea::fromDefaults(),
            new RFC3986Encoder(),
        );

        $this->pimple[UrlGenerator::class] = $routing->urlGenerator();
        $rf = $this->createResponseFactory();
        $this->pimple[ResponseFactory::class] = $rf;

        // Fetch one service from the routing facade in order to trigger Exceptions.
        // If we don't do this we need to fetch an extra service in every test case where we assert Exceptions
        // since everything is lazy by default
        $routing->urlMatcher();
        $this->routing = $routing;

        return $routing;
    }

    private function newPipeline(): MiddlewarePipeline
    {
        $error_handler = new NullErrorHandler();

        unset($this->pimple[RoutingMiddleware::class], $this->pimple[RouteRunner::class]);

        $this->pimple[RoutingMiddleware::class] = fn (): RoutingMiddleware => new RoutingMiddleware(
            $this->routing->urlMatcher()
        );

        $this->pimple[RouteRunner::class] = fn (): RouteRunner => new RouteRunner(
            new MiddlewarePipeline($this->psr_container, $error_handler, ),
            new MiddlewareResolver(
                $this->always_run,
                $this->middleware_aliases,
                $this->middleware_groups,
                $this->middleware_priority
            ),
            $this->psr_container
        );

        return (new MiddlewarePipeline($this->psr_container, $error_handler))->through([
            RoutingMiddleware::class,
            RouteRunner::class,
        ]);
    }

    private function nullLoader(): RouteLoader
    {
        return new class() implements RouteLoader {
            public function loadWebRoutes(WebRoutingConfigurator $configurator): void
            {
            }

            public function loadAdminRoutes(AdminRoutingConfigurator $configurator): void
            {
            }
        };
    }
}
