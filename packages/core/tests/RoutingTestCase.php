<?php

declare(strict_types=1);

namespace Tests\Core;

use Mockery;
use Snicco\Core\Support\WP;
use Snicco\View\ViewEngine;
use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Pipeline;
use Snicco\Core\Http\HttpKernel;
use Snicco\Testing\TestResponse;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Support\CacheFile;
use Snicco\Core\Routing\UrlMatcher;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Routing\UrlGenerator;
use Tests\Codeception\shared\UnitTest;
use Psr\Http\Message\ResponseInterface;
use Snicco\Testing\TestResponseEmitter;
use Snicco\Core\Routing\AdminDashboard;
use Snicco\Core\Routing\Internal\Router;
use Snicco\Core\Shared\ContainerAdapter;
use Snicco\Core\Http\ResponsePreparation;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Http\FileTemplateRenderer;
use Snicco\Core\Middleware\MiddlewareStack;
use Snicco\Core\Contracts\ExceptionHandler;
use Snicco\Core\Controllers\ViewController;
use Snicco\Core\Factories\MiddlewareFactory;
use Snicco\Core\Middleware\Core\RouteRunner;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Core\Routing\RoutingConfigurator;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Middleware\Core\ShareCookies;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\Middleware\Core\MethodOverride;
use Snicco\Core\Controllers\FallBackController;
use Snicco\Core\Factories\RouteConditionFactory;
use Snicco\Core\Routing\Internal\RequestContext;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\BarMiddleware;
use Tests\Core\fixtures\Middleware\BazMiddleware;
use Snicco\Core\Routing\Internal\WPAdminDashboard;
use Snicco\Core\Middleware\Core\RoutingMiddleware;
use Tests\Core\fixtures\TestDoubles\TestViewFactory;
use Tests\Core\fixtures\Middleware\FoobarMiddleware;
use Snicco\Core\Middleware\Core\SetRequestAttributes;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\Core\ExceptionHandling\NullExceptionHandler;
use Snicco\Core\Middleware\Core\AllowMatchingAdminRoutes;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
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
    
    private Router          $router;
    private HttpKernel      $kernel;
    private AdminDashboard  $admin_dashboard;
    private RequestContext  $request_context;
    private MiddlewareStack $middleware_stack;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->resetGlobalState();
        $this->createInstances();
        $this->container[RoutingTestController::class] = new RoutingTestController();
    }
    
    protected function tearDown() :void
    {
        $this->resetGlobalState();
        parent::tearDown();
        Mockery::close();
        WP::reset();
    }
    
    protected function assertEmptyBody(Request $request)
    {
        $this->assertResponseBody('', $request);
    }
    
    protected function assertResponseBody($expected, Request $request)
    {
        $this->runKernel($request)->assertSee($expected);
    }
    
    protected function withMiddlewareGroups(array $middlewares)
    {
        foreach ($middlewares as $name => $middleware) {
            $this->middleware_stack->withMiddlewareGroup($name, $middleware);
        }
    }
    
    protected function withMiddlewareAlias(array $aliases)
    {
        $this->middleware_stack->middlewareAliases($aliases);
    }
    
    protected function withMiddlewarePriority(array $array)
    {
        $this->middleware_stack->middlewarePriority($array);
    }
    
    protected function resetGlobalState()
    {
        $GLOBALS['test'] = [];
        $GLOBALS['wp_filter'] = [];
        $GLOBALS['wp_actions'] = [];
        $GLOBALS['wp_current_filter'] = [];
    }
    
    protected function runKernel(Request $request) :TestResponse
    {
        $this->withMiddlewareAlias($this->defaultMiddlewareAliases());
        
        $response = $this->kernel->run($request);
        return new TestResponse($response);
    }
    
    protected function routeConfigurator() :RoutingConfigurator
    {
        return $this->router;
    }
    
    protected function refreshRouter(CacheFile $cache_file = null, array $config = [])
    {
        unset($this->container[RoutingMiddleware::class]);
        unset($this->container[UrlGenerator::class]);
        unset($this->container[UrlMatcher::class]);
        unset($this->container[RoutingMiddleware::class]);
        
        $this->request_context ??= new RequestContext(
            new Request(
                $this->psrServerRequestFactory()->createServerRequest(
                    'GET',
                    'https://'.$this->app_domain
                )
            ),
            $this->admin_dashboard ??= WPAdminDashboard::fromDefaults()
        );
        
        $this->router = new Router(
            $this->container[RouteConditionFactory::class],
            $this->request_context,
            $config,
            $cache_file,
        );
        $this->container->instance(UrlGenerator::class, $this->router);
        $this->container->instance(RoutingMiddleware::class, new RoutingMiddleware($this->router));
        $this->generator = $this->router;
    }
    
    protected function refreshUrlGenerator(RequestContext $context = null) :UrlGenerator
    {
        if ($context) {
            $this->request_context = $context;
        }
        $this->refreshRouter();
        $this->generator = $this->router;
        return $this->generator;
    }
    
    protected function adminDashboard() :AdminDashboard
    {
        return $this->admin_dashboard;
    }
    
    private function defaultMiddlewareAliases() :array
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
    private function createInstances()
    {
        $this->container = $this->createContainer();
        $this->container->instance(ContainerAdapter::class, $this->container);
        
        $this->admin_dashboard = WPAdminDashboard::fromDefaults();
        $this->container[AdminDashboard::class] = $this->admin_dashboard;
        
        $condition_factory = new RouteConditionFactory($this->container);
        $this->container[RouteConditionFactory::class] = $condition_factory;
        
        $this->refreshRouter();
        
        $this->response_factory = $this->createResponseFactory();
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
            new Pipeline(
                $middleware_factory = new MiddlewareFactory($this->container),
                $error_handler,
                $this->response_factory,
            ),
            new TestResponseEmitter(new ResponsePreparation($this->psrStreamFactory())),
            $this->event_dispatcher =
                new FakeDispatcher(
                    new EventDispatcher(new DependencyInversionListenerFactory($this->container))
                )
        );
        
        // Middleware
        $this->middleware_stack = new MiddlewareStack();
        $this->container->instance(MiddlewareStack::class, $this->middleware_stack);
        
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
                new Pipeline(
                    $middleware_factory,
                    $error_handler,
                ),
                $this->middleware_stack,
                $this->container,
            )
        );
        $this->container->instance(SetRequestAttributes::class, new SetRequestAttributes());
        $this->container->instance(
            EvaluateResponseAbstractMiddleware::class,
            new EvaluateResponseAbstractMiddleware()
        );
        $this->container->instance(ShareCookies::class, new ShareCookies());
        $this->container->instance(
            AllowMatchingAdminRoutes::class,
            new AllowMatchingAdminRoutes($this->admin_dashboard)
        );
        $this->container->instance(
            FallBackController::class,
            new FallBackController()
        );
        $this->container->instance(
            ViewController::class,
            new ViewController(new FileTemplateRenderer())
        );
    }
    
    private function outputBufferMiddleware() :AbstractMiddleware
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