<?php

declare(strict_types=1);

namespace Tests\Core;

use Closure;
use Mockery;
use Snicco\Support\WP;
use Snicco\Http\Delegate;
use Snicco\Routing\Router;
use Snicco\Http\HttpKernel;
use Snicco\View\ViewEngine;
use Snicco\Routing\Pipeline;
use Snicco\Http\MethodField;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;
use Snicco\Http\ResponseFactory;
use Snicco\Http\ResponseEmitter;
use Snicco\Contracts\Middleware;
use Snicco\Shared\ContainerAdapter;
use Snicco\Middleware\MiddlewareStack;
use Snicco\Contracts\ExceptionHandler;
use Tests\Codeception\shared\UnitTest;
use Snicco\Contracts\RouteUrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Snicco\Factories\MiddlewareFactory;
use Snicco\Factories\RouteActionFactory;
use Snicco\Middleware\Core\MethodOverride;
use Snicco\Routing\RoutingServiceProvider;
use Snicco\Factories\RouteConditionFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Testing\TestDoubles\TestMagicLink;
use Snicco\Contracts\RouteCollectionInterface;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Tests\Core\fixtures\TestDoubles\HeaderStack;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\BarMiddleware;
use Tests\Core\fixtures\Middleware\BazMiddleware;
use Tests\Core\fixtures\Conditions\TrueCondition;
use Snicco\ExceptionHandling\NullExceptionHandler;
use Snicco\Middleware\Core\OutputBufferMiddleware;
use Tests\Core\fixtures\Conditions\FalseCondition;
use Tests\Core\fixtures\Conditions\MaybeCondition;
use Snicco\Routing\FastRoute\FastRouteUrlGenerator;
use Tests\Core\fixtures\Conditions\UniqueCondition;
use Tests\Core\fixtures\Middleware\FooBarMiddleware;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Tests\Codeception\shared\helpers\CreateRouteMatcher;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Tests\Codeception\shared\helpers\CreateRouteCollection;
use Tests\Core\fixtures\Conditions\ConditionWithDependency;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;
use Snicco\EventDispatcher\DependencyInversionListenerFactory;

use const TEST_APP_KEY;

class RoutingTestCase extends UnitTest
{
    
    use CreatePsr17Factories;
    use CreateContainer;
    use CreateRouteMatcher;
    use CreateRouteCollection;
    use CreateDefaultWpApiMocks;
    use CreatePsrRequests;
    
    protected MiddlewareStack          $middleware_stack;
    protected ExceptionHandler         $error_handler;
    protected ResponseFactory          $response_factory;
    protected HttpKernel               $kernel;
    protected Router                   $router;
    protected ContainerAdapter         $container;
    protected RouteCollectionInterface $routes;
    protected FakeDispatcher           $event_dispatcher;
    protected ViewEngine               $view_engine;
    private int                        $output_buffer_level;
    
    protected function setUp() :void
    {
        parent::setUp();
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
        $this->assertSame(
            $expected,
            $actual = $this->runKernel($request),
            "Expected output:[{$expected}] Received:['{$actual}']."
        );
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
            'foo' => FooMiddleware::class,
            'bar' => BarMiddleware::class,
            'baz' => BazMiddleware::class,
            'foobar' => FooBarMiddleware::class,
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
        
        $condition_factory =
            new RouteConditionFactory($this->defaultConditions(), $this->container);
        $handler_factory = new RouteActionFactory([], $this->container);
        $this->container->instance(RouteConditionFactory::class, $condition_factory);
        $this->container->instance(RouteActionFactory::class, $handler_factory);
        $this->container->instance(MiddlewareStack::class, $this->middleware_stack);
        $this->container->instance(ResponseFactory::class, $this->response_factory);
        $this->container->instance(StreamFactoryInterface::class, $this->response_factory);
        $this->container->instance(
            MethodOverride::class,
            new MethodOverride(new MethodField(TEST_APP_KEY))
        );
        $this->container->instance(RouteCollectionInterface::class, $this->routes);
        $this->container->instance(ExceptionHandler::class, $this->error_handler);
        $this->container->instance(OutputBufferMiddleware::class, $this->outputBufferMiddleware());
        $this->container->instance(
            RouteUrlGenerator::class,
            new FastRouteUrlGenerator($this->routes)
        );
        $this->container->instance(MagicLink::class, new TestMagicLink());
        $this->container->instance(ViewEngine::class, $this->view_engine);
        
        $this->kernel = new HttpKernel(
            new Pipeline(
                new MiddlewareFactory($this->container),
                $this->error_handler,
                $this->response_factory,
            ),
            $this->container->make(ResponseEmitter::class),
            $this->event_dispatcher =
                new FakeDispatcher(
                    new EventDispatcher(new DependencyInversionListenerFactory($this->container))
                )
        );
    }
    
    private function outputBufferMiddleware() :Middleware
    {
        return new class extends Middleware
        {
            
            public function handle(Request $request, Delegate $next) :ResponseInterface
            {
                return $next($request);
            }
            
        };
    }
    
}