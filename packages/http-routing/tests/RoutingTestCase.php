<?php

declare(strict_types=1);

namespace Tests\HttpRouting;

use Snicco\Core\DIContainer;
use Snicco\Testing\TestResponse;
use Snicco\Core\Utils\PHPCacheFile;
use Snicco\HttpRouting\Routing\Router;
use Tests\Codeception\shared\UnitTest;
use Snicco\HttpRouting\Http\Redirector;
use Snicco\HttpRouting\Http\HttpKernel;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\ResponseFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Tests\HttpRouting\fixtures\FooMiddleware;
use Tests\HttpRouting\fixtures\BarMiddleware;
use Tests\HttpRouting\fixtures\BazMiddleware;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\HttpRouting\Middleware\ShareCookies;
use Snicco\HttpRouting\Http\ResponsePreparation;
use Tests\HttpRouting\fixtures\FoobarMiddleware;
use Snicco\HttpRouting\Http\FileTemplateRenderer;
use Snicco\HttpRouting\Middleware\MethodOverride;
use Snicco\HttpRouting\Middleware\MustMatchRoute;
use Snicco\Core\ExceptionHandling\ExceptionHandler;
use Snicco\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\HttpRouting\Middleware\Internal\RouteRunner;
use Snicco\Core\ExceptionHandling\NullExceptionHandler;
use Snicco\HttpRouting\Routing\AdminDashboard\AdminArea;
use Snicco\HttpRouting\Routing\Controller\ViewController;
use Snicco\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\HttpRouting\Routing\AdminDashboard\WPAdminArea;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\HttpRouting\Middleware\Internal\MiddlewareStack;
use Snicco\HttpRouting\Middleware\Internal\PrepareResponse;
use Snicco\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\HttpRouting\Middleware\Internal\MiddlewareFactory;
use Snicco\HttpRouting\Middleware\Internal\RoutingMiddleware;
use Snicco\HttpRouting\Routing\Controller\FallBackController;
use Snicco\HttpRouting\Routing\Controller\RedirectController;
use Snicco\HttpRouting\Middleware\Internal\MiddlewarePipeline;
use Snicco\HttpRouting\Routing\Condition\RouteConditionFactory;
use Tests\HttpRouting\fixtures\Controller\RoutingTestController;
use Snicco\HttpRouting\Routing\UrlGenerator\UrlGeneratorFactory;
use Snicco\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Core\EventDispatcher\DependencyInversionListenerFactory;
use Snicco\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\HttpRouting\Routing\RoutingConfigurator\RoutingConfiguratorUsingRouter;

/**
 * @interal
 */
class RoutingTestCase extends UnitTest
{
    
    const CONTROLLER_NAMESPACE = 'Tests\\HttpRouting\\fixtures\\Controller';
    
    use CreatePsr17Factories;
    use CreateContainer;
    use CreatePsrRequests;
    
    protected string          $app_domain = 'foobar.com';
    protected string          $routes_dir;
    protected ResponseFactory $response_factory;
    protected DIContainer     $container;
    protected FakeDispatcher  $event_dispatcher;
    protected UrlGenerator    $generator;
    
    private Router                 $router;
    private HttpKernel             $kernel;
    private AdminArea              $admin_dashboard;
    private UrlGenerationContext   $request_context;
    private MiddlewareStack        $middleware_stack;
    private WebRoutingConfigurator $routing_configurator;
    private MiddlewarePipeline     $pipeline;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->createNeededCollaborators();
        $this->container[RoutingTestController::class] = new RoutingTestController();
        $this->routes_dir = __DIR__.'/fixtures/routes';
    }
    
    final protected function assertEmptyBody(Request $request)
    {
        $this->assertResponseBody('', $request);
    }
    
    final protected function assertResponseBody($expected, Request $request)
    {
        $response = $this->runKernel($request);
        $this->assertSame(
            $expected,
            $b = $response->body(),
            "Expected response body [$expected] for path [{$request->path()}].\nGot [$b]."
        );
    }
    
    /**
     * @param  array<string,array<string>>  $middlewares
     */
    final protected function withMiddlewareGroups(array $middlewares) :void
    {
        foreach ($middlewares as $name => $middleware) {
            $this->middleware_stack->withMiddlewareGroup($name, $middleware);
        }
    }
    
    final protected function withGlobalMiddleware(array $middleware)
    {
        $this->withMiddlewareGroups([RoutingConfigurator::GLOBAL_MIDDLEWARE => $middleware]);
    }
    
    final protected function withNewMiddlewareStack(MiddlewareStack $middleware_stack)
    {
        $this->middleware_stack = $middleware_stack;
        $this->container[MiddlewareStack::class] = $middleware_stack;
        $this->container[RouteRunner::class] = new RouteRunner(
            $this->pipeline,
            $middleware_stack,
            $this->container
        );
    }
    
    final protected function withMiddlewareAlias(array $aliases)
    {
        $this->middleware_stack->middlewareAliases($aliases);
    }
    
    final protected function withMiddlewarePriority(array $array)
    {
        $this->middleware_stack->middlewarePriority($array);
    }
    
    final protected function runKernel(Request $request) :TestResponse
    {
        $this->withMiddlewareAlias($this->defaultMiddlewareAliases());
        
        $response = $this->kernel->handle($request);
        return new TestResponse($response);
    }
    
    final protected function routeConfigurator() :WebRoutingConfigurator
    {
        return $this->routing_configurator;
    }
    
    final protected function adminRouteConfigurator() :AdminRoutingConfigurator
    {
        return $this->routing_configurator;
    }
    
    final protected function refreshRouter(PHPCacheFile $cache_file = null, UrlGenerationContext $context = null, array $config = [])
    {
        unset($this->container[RoutingMiddleware::class]);
        unset($this->container[UrlGenerator::class]);
        unset($this->container[UrlMatcher::class]);
        
        if (is_null($context)) {
            $context = $this->request_context ?? UrlGenerationContext::forConsole(
                    $this->app_domain,
                );
        }
        
        $this->request_context = $context;
        
        $this->admin_dashboard ??= WPAdminArea::fromDefaults();
        
        $this->router = new Router(
            $this->container[RouteConditionFactory::class],
            new UrlGeneratorFactory(
                $context,
                $this->admin_dashboard,
                new RFC3986Encoder(),
            ),
            $this->admin_dashboard,
            $cache_file
        );
        
        $this->routing_configurator = new RoutingConfiguratorUsingRouter(
            $this->router,
            $this->admin_dashboard->urlPrefix(),
            $config
        );
        
        $this->container->instance(UrlGenerator::class, $this->router);
        $this->container->instance(RoutingMiddleware::class, new RoutingMiddleware($this->router));
        $this->container->instance(UrlMatcher::class, $this->router);
        $this->generator = $this->router;
    }
    
    final protected function refreshUrlGenerator(UrlGenerationContext $context = null) :UrlGenerator
    {
        $this->refreshRouter(null, $context);
        return $this->generator;
    }
    
    final protected function adminDashboard() :AdminArea
    {
        return $this->admin_dashboard;
    }
    
    final private function defaultMiddlewareAliases() :array
    {
        return [
            'foo' => FooMiddleware::class,
            'bar' => BarMiddleware::class,
            'baz' => BazMiddleware::class,
            'foobar' => FoobarMiddleware::class,
        ];
    }
    
    /**
     * Create instances that are necessary for running routes.
     */
    final private function createNeededCollaborators()
    {
        $this->container = $this->createContainer();
        $this->container->instance(DIContainer::class, $this->container);
        
        $this->admin_dashboard = WPAdminArea::fromDefaults();
        $this->container[AdminArea::class] = $this->admin_dashboard;
        
        $condition_factory = new RouteConditionFactory($this->container);
        $this->container[RouteConditionFactory::class] = $condition_factory;
        
        $this->refreshRouter();
        
        $this->response_factory = $this->createResponseFactory($this->generator);
        $this->container->instance(ResponseFactory::class, $this->response_factory);
        $this->container->instance(Redirector::class, $this->response_factory);
        $this->container->instance(StreamFactoryInterface::class, $this->response_factory);
        
        $error_handler = new NullExceptionHandler();
        $this->container->instance(ExceptionHandler::class, $error_handler);
        
        $this->kernel = new HttpKernel(
            $this->pipeline = new MiddlewarePipeline(
                $middleware_factory = new MiddlewareFactory($this->container),
                $error_handler,
            ),
            $this->event_dispatcher =
                new FakeDispatcher(
                    new EventDispatcher(new DependencyInversionListenerFactory($this->container))
                )
        );
        
        // Middleware
        $this->middleware_stack = new MiddlewareStack();
        $this->container->instance(MiddlewareStack::class, $this->middleware_stack);
        
        $this->container->instance(
            PrepareResponse::class,
            new PrepareResponse(new ResponsePreparation($this->psrStreamFactory()))
        );
        
        $this->container->instance(MethodOverride::class, new MethodOverride());
        
        $this->container->singleton(RoutingMiddleware::class, function () {
            return new RoutingMiddleware(
                $this->router,
            );
        });
        
        $this->container->instance(
            RouteRunner::class,
            new RouteRunner(
                new MiddlewarePipeline(
                    $middleware_factory,
                    $error_handler,
                ),
                $this->middleware_stack,
                $this->container,
            )
        );
        
        $this->container->instance(
            MustMatchRoute::class,
            new MustMatchRoute()
        );
        $this->container->instance(ShareCookies::class, new ShareCookies());
        
        // internal controllers
        $this->container->instance(FallBackController::class, new FallBackController());
        
        $this->container->instance(
            ViewController::class,
            new ViewController(new FileTemplateRenderer())
        );
        
        $this->container->instance(RedirectController::class, new RedirectController());
    }
    
}