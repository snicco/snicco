<?php

declare(strict_types=1);

namespace Tests\Core;

use Snicco\View\ViewEngine;
use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\HttpKernel;
use Snicco\Testing\TestResponse;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Support\CacheFile;
use Snicco\Core\Routing\UrlMatcher;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Routing\UrlGenerator;
use Tests\Codeception\shared\UnitTest;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Routing\AdminDashboard;
use Snicco\Core\Http\MiddlewarePipeline;
use Snicco\Core\Routing\Internal\Router;
use Snicco\Core\Shared\ContainerAdapter;
use Snicco\Core\Http\ResponsePreparation;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Http\FileTemplateRenderer;
use Snicco\Core\Middleware\MiddlewareStack;
use Snicco\Core\Contracts\ExceptionHandler;
use Snicco\Core\Controllers\ViewController;
use Snicco\Core\Middleware\Core\RouteRunner;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Core\Routing\RoutingConfigurator;
use Snicco\Core\Middleware\MiddlewareFactory;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Middleware\Core\ShareCookies;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\Middleware\Core\MethodOverride;
use Snicco\Core\Controllers\FallBackController;
use Snicco\Core\Controllers\RedirectController;
use Snicco\Core\Routing\Internal\RFC3986Encoder;
use Snicco\Core\Middleware\Core\PrepareResponse;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\BarMiddleware;
use Tests\Core\fixtures\Middleware\BazMiddleware;
use Snicco\Core\Routing\Internal\WPAdminDashboard;
use Snicco\Core\Middleware\Core\RoutingMiddleware;
use Tests\Core\fixtures\TestDoubles\TestViewFactory;
use Tests\Core\fixtures\Middleware\FoobarMiddleware;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\Core\Routing\Internal\UrlGeneratorFactory;
use Snicco\Core\Routing\Internal\UrlGenerationContext;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\Core\Routing\Internal\RouteConditionFactory;
use Snicco\Core\ExceptionHandling\NullExceptionHandler;
use Snicco\Core\Middleware\Core\AllowMatchingAdminRoutes;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\Core\Routing\Internal\RoutingConfiguratorFactory;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Middleware\Core\OutputBufferAbstractMiddleware;
use Snicco\Core\Middleware\Core\EvaluateResponseAbstractMiddleware;
use Snicco\Core\EventDispatcher\DependencyInversionListenerFactory;

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
    protected ResponseFactory  $response_factory;
    protected ContainerAdapter $container;
    protected FakeDispatcher   $event_dispatcher;
    protected UrlGenerator     $generator;
    
    private Router               $router;
    private HttpKernel           $kernel;
    private AdminDashboard       $admin_dashboard;
    private UrlGenerationContext $request_context;
    private MiddlewareStack      $middleware_stack;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->createNeededCollaborators();
        $this->container[RoutingTestController::class] = new RoutingTestController();
    }
    
    final protected function assertEmptyBody(Request $request)
    {
        $this->assertResponseBody('', $request);
    }
    
    final protected function assertResponseBody($expected, Request $request)
    {
        $response = $this->runKernel($request);
        $this->assertSame($expected, $response->body());
    }
    
    final protected function withMiddlewareGroups(array $middlewares)
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
    
    final protected function routeConfigurator() :RoutingConfigurator
    {
        return $this->router;
    }
    
    final protected function refreshRouter(CacheFile $cache_file = null, UrlGenerationContext $context = null, array $config = [])
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
        
        $this->router = new Router(
            $this->container[RouteConditionFactory::class],
            new RoutingConfiguratorFactory($config),
            new UrlGeneratorFactory(
                $context,
                $this->admin_dashboard ??= WPAdminDashboard::fromDefaults(),
                new RFC3986Encoder(),
            ),
            $this->admin_dashboard->urlPrefix(),
            $cache_file
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
            EvaluateResponseAbstractMiddleware::class,
            new EvaluateResponseAbstractMiddleware()
        );
        $this->container->instance(ShareCookies::class, new ShareCookies());
        
        $this->container->instance(
            AllowMatchingAdminRoutes::class,
            new AllowMatchingAdminRoutes($this->admin_dashboard)
        );
        
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