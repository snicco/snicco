<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Component\HttpRouting\Http\FileTemplateRenderer;
use Snicco\Component\HttpRouting\Http\MethodOverride;
use Snicco\Component\HttpRouting\Http\NegotiateContent;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Http\ResponsePreparation;
use Snicco\Component\HttpRouting\HttpKernel;
use Snicco\Component\HttpRouting\KernelMiddleware;
use Snicco\Component\HttpRouting\MiddlewarePipeline;
use Snicco\Component\HttpRouting\MiddlewareResolver;
use Snicco\Component\HttpRouting\PrepareResponse;
use Snicco\Component\HttpRouting\RouteRunner;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Controller\FallBackController;
use Snicco\Component\HttpRouting\Routing\Controller\RedirectController;
use Snicco\Component\HttpRouting\Routing\Controller\ViewController;
use Snicco\Component\HttpRouting\Routing\RouteLoading\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\Routing;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\RoutingMiddleware;
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
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsrContainer;
use Snicco\Component\Kernel\DIContainer;

use function array_merge;

/**
 * @interal
 */
class HttpRunnerTestCase extends TestCase
{

    use CreateTestPsr17Factories;
    use CreatesPsrRequests;
    use CreateTestPsrContainer;
    use CreateHttpErrorHandler;

    const CONTROLLER_NAMESPACE = 'Snicco\\Component\\HttpRouting\\Tests\\fixtures\\Controller';

    protected string $app_domain = 'foobar.com';
    protected string $routes_dir;
    protected Routing $routing;
    protected DIContainer $container;

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

        $this->container = $this->createContainer();

        $this->routing = $this->newRoutingFacade();

        // internal controllers
        $this->container->instance(FallBackController::class, new FallBackController());
        $this->container->instance(ViewController::class, new ViewController(new FileTemplateRenderer()));
        $this->container->instance(RedirectController::class, new RedirectController());

        // TestController
        $this->container[RoutingTestController::class] = new RoutingTestController();

        $this->routes_dir = __DIR__ . '/fixtures/routes';
    }

    final protected function newRoutingFacade(UrlGenerationContext $context = null): Routing
    {
        $context = $context ?: UrlGenerationContext::forConsole($this->app_domain);

        $routing = new Routing(
            $this->container,
            $context,
            new DefaultRouteLoadingOptions('/api'),
            null,
            WPAdminArea::fromDefaults(),
            new RFC3986Encoder(),
        );

        $this->container->instance(UrlGeneratorInterface::class, $routing->urlGenerator());
        $rf = $this->createResponseFactory($routing->urlGenerator());
        $this->container->instance(ResponseFactory::class, $rf);
        $this->container->instance(Redirector::class, $rf);
        $this->container->instance(StreamFactoryInterface::class, $rf);

        return $routing;
    }

    final protected function assertEmptyBody(Request $request): void
    {
        $this->assertResponseBody('', $request);
    }

    final protected function assertResponseBody(string $expected, Request $request): void
    {
        $response = $this->runKernel($request);
        $this->assertSame(
            $expected,
            $b = $response->body(),
            "Expected response body [$expected] for path [{$request->path()}].\nGot [$b]."
        );
    }

    final protected function runKernel(Request $request): AssertableResponse
    {
        $kernel = $this->newKernel();
        $response = $kernel->handle($request);
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

    final protected function routeConfigurator(): WebRoutingConfigurator
    {
        return $this->routing->webConfigurator();
    }

    final protected function adminRouteConfigurator(): AdminRoutingConfigurator
    {
        return $this->routing->adminConfigurator();
    }

    final protected function newUrlGenerator(UrlGenerationContext $context = null): UrlGeneratorInterface
    {
        $this->routing = $this->newRoutingFacade($context);
        return $this->routing->urlGenerator();
    }

    final protected function generator(): UrlGeneratorInterface
    {
        return $this->routing->urlGenerator();
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

    private function newKernel(): HttpKernel
    {
        $error_handler = new NullErrorHandler();

        $route_runner = new RouteRunner(
            new MiddlewarePipeline(
                $this->container,
                $error_handler,
            ),
            new MiddlewareResolver(
                $this->always_run,
                $this->middleware_aliases,
                $this->middleware_groups,
                $this->middleware_priority
            ),
            $this->container
        );


        $kernel_middleware = new KernelMiddleware(
            new NegotiateContent(['en']),
            new PrepareResponse(new ResponsePreparation($this->psrStreamFactory())),
            new MethodOverride(),
            new RoutingMiddleware($this->routing->urlMatcher(),),
            $route_runner
        );

        return new HttpKernel(
            $kernel_middleware,
            new MiddlewarePipeline(
                $this->container,
                $error_handler,
            ),
            new class implements EventDispatcherInterface {

                public function dispatch(object $event): void
                {
                    //
                }

            }
        );
    }

}