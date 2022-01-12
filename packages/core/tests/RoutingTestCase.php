<?php

declare(strict_types=1);

namespace Tests\Core;

use Snicco\View\ViewEngine;
use Snicco\Core\Routing\Router;
use Snicco\Core\Http\HttpKernel;
use Snicco\Testing\TestResponse;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Delegate;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Support\PHPCacheFile;
use Tests\Codeception\shared\UnitTest;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Shared\ContainerAdapter;
use Snicco\Core\Middleware\ShareCookies;
use Snicco\Core\Http\ResponsePreparation;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Http\FileTemplateRenderer;
use Snicco\Core\Middleware\MethodOverride;
use Snicco\Core\Middleware\MustMatchRoute;
use Snicco\Core\Contracts\ExceptionHandler;
use Snicco\Core\Controllers\ViewController;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Routing\UrlMatcher\UrlMatcher;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\Controllers\FallBackController;
use Snicco\Core\Controllers\RedirectController;
use Snicco\Core\Middleware\Internal\RouteRunner;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\BarMiddleware;
use Tests\Core\fixtures\Middleware\BazMiddleware;
use Snicco\Core\Routing\UrlGenerator\UrlGenerator;
use Snicco\Core\Middleware\Internal\MiddlewareStack;
use Snicco\Core\Middleware\Internal\PrepareResponse;
use Snicco\Core\Routing\UrlGenerator\RFC3986Encoder;
use Tests\Core\fixtures\TestDoubles\TestViewFactory;
use Tests\Core\fixtures\Middleware\FoobarMiddleware;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\Core\Middleware\Internal\MiddlewareFactory;
use Snicco\Core\Middleware\Internal\RoutingMiddleware;
use Snicco\Core\Routing\AdminDashboard\AdminDashboard;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\Core\Middleware\Internal\MiddlewarePipeline;
use Snicco\Core\ExceptionHandling\NullExceptionHandler;
use Snicco\Core\Routing\AdminDashboard\WPAdminDashboard;
use Snicco\Core\Routing\Condition\RouteConditionFactory;
use Snicco\Core\Routing\UrlGenerator\UrlGeneratorFactory;
use Snicco\Core\Routing\UrlGenerator\UrlGenerationContext;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\Core\Middleware\OutputBufferAbstractMiddleware;
use Snicco\Core\Middleware\Internal\AllowMatchingAdminRoutes;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Core\EventDispatcher\DependencyInversionListenerFactory;
use Snicco\Core\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Core\Routing\RoutingConfigurator\RoutingConfiguratorUsingRouter;

/**
 * @interal
 */
class RoutingTestCase extends UnitTest
{
    
    const CONTROLLER_NAMESPACE = 'Tests\\Core\\fixtures\\Controllers\\Web';
    
    use CreatePsr17Factories;
    use CreateContainer;
    use CreatePsrRequests;
    
    protected string           $app_domain = 'foobar.com';
    protected string           $routes_dir;
    protected ResponseFactory  $response_factory;
    protected ContainerAdapter $container;
    protected FakeDispatcher   $event_dispatcher;
    protected UrlGenerator     $generator;
    
    private Router                 $router;
    private HttpKernel             $kernel;
    private AdminDashboard         $admin_dashboard;
    private UrlGenerationContext   $request_context;
    private MiddlewareStack        $middleware_stack;
    private WebRoutingConfigurator $routing_configurator;
    
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
        
        $this->admin_dashboard ??= WPAdminDashboard::fromDefaults();
        
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
    
    final protected function adminDashboard() :AdminDashboard
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
        $this->container->instance(ContainerAdapter::class, $this->container);
        
        $this->admin_dashboard = WPAdminDashboard::fromDefaults();
        $this->container[AdminDashboard::class] = $this->admin_dashboard;
        
        $condition_factory = new RouteConditionFactory($this->container);
        $this->container[RouteConditionFactory::class] = $condition_factory;
        
        $this->refreshRouter();
        
        $this->response_factory = $this->createResponseFactory($this->generator);
        $this->container->instance(ResponseFactory::class, $this->response_factory);
        $this->container->instance(Redirector::class, $this->response_factory);
        $this->container->instance(StreamFactoryInterface::class, $this->response_factory);
        
        $error_handler = new NullExceptionHandler();
        $this->container->instance(ExceptionHandler::class, $error_handler);
        
        $this->container->instance(
            ViewEngine::class,
            new ViewEngine(new TestViewFactory())
        );
        
        $this->kernel = new HttpKernel(
            new MiddlewarePipeline(
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
        
        $this->container->instance(
            OutputBufferAbstractMiddleware::class,
            $this->outputBufferMiddleware()
        );
        
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
    
    final private function outputBufferMiddleware() :AbstractMiddleware
    {
        return new class extends AbstractMiddleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $next($request);
            }
            
        };
    }
    
}