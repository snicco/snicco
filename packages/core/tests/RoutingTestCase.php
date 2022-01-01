<?php

declare(strict_types=1);

namespace Tests\Core;

use Closure;
use Mockery;
use Snicco\Core\Support\WP;
use Snicco\View\ViewEngine;
use Snicco\Core\Routing\Router;
use Snicco\Core\Http\HttpKernel;
use Snicco\Core\Routing\Delegate;
use Snicco\Core\Routing\Pipeline;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\ResponseEmitter;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Routing\UrlGenerator;
use Snicco\Core\Contracts\Redirector;
use Tests\Codeception\shared\UnitTest;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Shared\ContainerAdapter;
use Snicco\Core\Http\ResponsePreparation;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Middleware\MiddlewareStack;
use Snicco\Core\Contracts\ExceptionHandler;
use Snicco\Core\Controllers\ViewAbstractController;
use Snicco\Core\Factories\MiddlewareFactory;
use Snicco\Core\Middleware\Core\RouteRunner;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Core\Http\DefaultResponseFactory;
use Snicco\Core\Factories\RouteActionFactory;
use Snicco\Core\Middleware\Core\ShareCookies;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\Middleware\Core\MethodOverride;
use Snicco\Core\Routing\RoutingServiceProvider;
use Snicco\Core\Controllers\FallBackAbstractController;
use Snicco\Core\Factories\RouteConditionFactory;
use Tests\Core\fixtures\TestDoubles\HeaderStack;
use Tests\Core\fixtures\Middleware\FooAbstractMiddleware;
use Tests\Core\fixtures\Middleware\BarAbstractMiddleware;
use Tests\Core\fixtures\Middleware\BazAbstractMiddleware;
use Tests\Core\fixtures\Conditions\TrueCondition;
use Snicco\Core\Http\FileBasedHtmlResponseFactory;
use Snicco\Core\Middleware\Core\RoutingAbstractMiddleware;
use Tests\Core\fixtures\Conditions\FalseCondition;
use Tests\Core\fixtures\Conditions\MaybeCondition;
use Snicco\Core\Contracts\RouteCollectionInterface;
use Tests\Core\fixtures\Conditions\UniqueCondition;
use Tests\Core\fixtures\Middleware\FooBarAbstractMiddleware;
use Tests\Core\fixtures\TestDoubles\TestViewFactory;
use Snicco\Core\Routing\FastRoute\RouteUrlGenerator;
use Snicco\Core\Middleware\Core\SetRequestAttributes;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\Core\ExceptionHandling\NullExceptionHandler;
use Snicco\Core\Middleware\Core\OutputBufferAbstractMiddleware;
use Tests\Codeception\shared\helpers\AssertViewContent;
use Tests\Codeception\shared\helpers\CreateRouteMatcher;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\Core\Middleware\Core\EvaluateResponseAbstractMiddleware;
use Tests\Codeception\shared\helpers\CreateRouteCollection;
use Tests\Core\fixtures\Conditions\ConditionWithDependency;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;
use Snicco\Core\Middleware\Core\AllowMatchingAdminAndAjaxRoutes;
use Snicco\Core\EventDispatcher\DependencyInversionListenerFactory;

class RoutingTestCase extends UnitTest
{
    
    use AssertViewContent;
    use CreatePsr17Factories;
    use CreateContainer;
    use CreateRouteMatcher;
    use CreateRouteCollection;
    use CreateDefaultWpApiMocks;
    use CreatePsrRequests;
    
    protected MiddlewareStack  $middleware_stack;
    protected ExceptionHandler $error_handler;
    
    /**
     * @var DefaultResponseFactory
     */
    protected $response_factory;
    
    protected HttpKernel       $kernel;
    protected Router           $router;
    protected ContainerAdapter $container;
    protected FakeDispatcher   $event_dispatcher;
    protected ViewEngine       $view_engine;
    private int                $output_buffer_level;
    
    protected function setUp() :void
    {
        parent::setUp();
        if ( ! isset($this->app_domain)) {
            $this->app_domain = 'foobar.com';
        }
        $this->resetGlobalState();
        $this->createDefaultWpApiMocks();
        $this->createInstances();
        $this->createDefaultWpApiMocks();
        $this->output_buffer_level = ob_get_level();
        HeaderStack::reset();
    }
    
    protected function tearDown() :void
    {
        $this->resetGlobalState();
        parent::tearDown();
        Mockery::close();
        WP::reset();
        while (ob_get_level() > $this->output_buffer_level) {
            ob_end_clean();
        }
    }
    
    protected function createRoutes(Closure $routes, bool $force_trailing = false)
    {
        $this->router = new Router($this->routes, $force_trailing);
        $routes();
        $this->router->loadRoutes();
    }
    
    protected function assertEmptyResponse(Request $request)
    {
        $this->assertResponse('', $request);
    }
    
    protected function assertResponse($expected, Request $request)
    {
        $this->assertViewContent($expected, $this->runKernel($request));
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
    
    protected function defaultConditions() :array
    {
        return array_merge(RoutingServiceProvider::CONDITION_TYPES, [
            
            'true' => TrueCondition::class,
            'false' => FalseCondition::class,
            'maybe' => MaybeCondition::class,
            'unique' => UniqueCondition::class,
            'dependency_condition' => ConditionWithDependency::class,
        
        ]);
    }
    
    protected function defaultMiddlewareAliases() :array
    {
        return [
            'foo' => FooAbstractMiddleware::class,
            'bar' => BarAbstractMiddleware::class,
            'baz' => BazAbstractMiddleware::class,
            'foobar' => FooBarAbstractMiddleware::class,
        ];
    }
    
    protected function runKernel(Request $request)
    {
        $this->withMiddlewareAlias($this->defaultMiddlewareAliases());
        
        $this->output_buffer_level = ob_get_level();
        ob_start();
        $this->kernel->run($request);
        return ob_get_clean();
    }
    
    /**
     * Create instances that are necessary for running routes.
     */
    private function createInstances()
    {
        $this->container = $this->createContainer();
        $this->container->instance(ContainerAdapter::class, $this->container);
        $this->routes = $this->createRouteCollection();
        $this->error_handler = new NullExceptionHandler();
        $this->response_factory = $this->createResponseFactory();
        
        $this->middleware_stack = new MiddlewareStack();
        
        $condition_factory = new RouteConditionFactory(
            $this->defaultConditions(),
            $this->container
        );
        
        $handler_factory = new RouteActionFactory([], $this->container);
        
        $this->container->instance(RouteConditionFactory::class, $condition_factory);
        
        $this->container->instance(RouteActionFactory::class, $handler_factory);
        
        $this->container->instance(MiddlewareStack::class, $this->middleware_stack);
        
        $this->container->instance(ResponseFactory::class, $this->response_factory);
        $this->container->instance(Redirector::class, $this->response_factory);
        
        $this->container->instance(StreamFactoryInterface::class, $this->response_factory);
        
        $this->container->instance(
            MethodOverride::class,
            new MethodOverride()
        );
        
        $this->container->instance(RouteCollectionInterface::class, $this->routes);
        
        $this->container->instance(ExceptionHandler::class, $this->error_handler);
        
        $this->container->instance(
            OutputBufferAbstractMiddleware::class,
            $this->outputBufferMiddleware()
        );
        
        $this->container->instance(
            ViewEngine::class,
            $this->view_engine = new ViewEngine(new TestViewFactory())
        );
        
        $this->container->instance(
            UrlGenerator::class,
            $this->generator,
        );
        
        $this->kernel = new HttpKernel(
            new Pipeline(
                $middleware_factory = new MiddlewareFactory($this->container),
                $this->error_handler,
                $this->response_factory,
            ),
            new ResponseEmitter(new ResponsePreparation($this->psrStreamFactory())),
            $this->event_dispatcher =
                new FakeDispatcher(
                    new EventDispatcher(new DependencyInversionListenerFactory($this->container))
                )
        );
        
        // Middleware
        $this->container->singleton(RoutingAbstractMiddleware::class,
            function () use ($condition_factory) {
                return new RoutingAbstractMiddleware(
                    $this->routes,
                    $condition_factory
                );
            }
        );
        $this->container->instance(
            RouteRunner::class,
            new RouteRunner(
                new Pipeline(
                    $middleware_factory,
                    $this->error_handler,
                    $this->response_factory,
                ),
                $this->middleware_stack,
                $handler_factory
            )
        );
        $this->container->instance(SetRequestAttributes::class, new SetRequestAttributes());
        $this->container->instance(
            EvaluateResponseAbstractMiddleware::class,
            new EvaluateResponseAbstractMiddleware()
        );
        $this->container->instance(ShareCookies::class, new ShareCookies());
        $this->container->instance(
            AllowMatchingAdminAndAjaxRoutes::class,
            new AllowMatchingAdminAndAjaxRoutes()
        );
        $this->container->instance(
            FallBackAbstractController::class,
            new FallBackAbstractController()
        );
        $this->container->instance(
            ViewAbstractController::class,
            new ViewAbstractController(new FileBasedHtmlResponseFactory())
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