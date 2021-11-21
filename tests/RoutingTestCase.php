<?php

declare(strict_types=1);

namespace Tests;

use Closure;
use Mockery;
use Snicco\Support\WP;
use Snicco\Events\Event;
use Snicco\Http\Delegate;
use Snicco\Routing\Router;
use Snicco\Http\HttpKernel;
use Snicco\View\MethodField;
use Snicco\Routing\Pipeline;
use Tests\stubs\HeaderStack;
use Snicco\Http\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Contracts\ContainerAdapter;
use Snicco\Contracts\MagicLink;
use Snicco\Http\ResponseFactory;
use Snicco\Http\ResponseEmitter;
use Snicco\Contracts\Middleware;
use Tests\stubs\TestViewFactory;
use Tests\concerns\CreateContainer;
use Tests\concerns\CreatePsrRequests;
use Tests\concerns\CreateRouteMatcher;
use Snicco\Middleware\MiddlewareStack;
use Snicco\Contracts\ExceptionHandler;
use Snicco\Contracts\RouteUrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Snicco\Factories\MiddlewareFactory;
use Tests\concerns\CreatePsr17Factories;
use Snicco\Factories\RouteActionFactory;
use Tests\concerns\CreateRouteCollection;
use Snicco\Middleware\Core\MethodOverride;
use Snicco\Routing\RoutingServiceProvider;
use Snicco\Contracts\ViewFactoryInterface;
use Snicco\Factories\RouteConditionFactory;
use Tests\concerns\CreateDefaultWpApiMocks;
use Snicco\EventDispatcher\EventDispatcher;
use Psr\Http\Message\StreamFactoryInterface;
use Tests\fixtures\Conditions\TrueCondition;
use Tests\fixtures\Middleware\FooMiddleware;
use Tests\fixtures\Middleware\BarMiddleware;
use Tests\fixtures\Middleware\BazMiddleware;
use Snicco\Testing\TestDoubles\TestMagicLink;
use Tests\fixtures\Conditions\FalseCondition;
use Tests\fixtures\Conditions\MaybeCondition;
use Snicco\Contracts\RouteCollectionInterface;
use Tests\fixtures\Conditions\UniqueCondition;
use Tests\fixtures\Middleware\FooBarMiddleware;
use Snicco\ExceptionHandling\NullExceptionHandler;
use Snicco\Middleware\Core\OutputBufferMiddleware;
use Snicco\Routing\FastRoute\FastRouteUrlGenerator;
use Tests\fixtures\Conditions\ConditionWithDependency;
use Snicco\Core\Events\DependencyInversionListenerFactory;

class RoutingTestCase extends TestCase
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
    
    private int $output_buffer_level;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->resetGlobalState();
        $this->createDefaultWpApiMocks();
        $this->createInstances();
        Event::make($this->container);
        Event::fake();
        $this->createDefaultWpApiMocks();
        $this->output_buffer_level = ob_get_level();
        HeaderStack::reset();
    }
    
    protected function tearDown() :void
    {
        $this->resetGlobalState();
        parent::tearDown();
        Event::setInstance(null);
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
        $this->container->instance(ViewFactoryInterface::class, new TestViewFactory());
        
        $this->kernel = new HttpKernel(
            new Pipeline(
                new MiddlewareFactory($this->container),
                $this->error_handler,
                $this->response_factory,
            ),
            $this->container->make(ResponseEmitter::class),
            $this->event_dispatcher =
                new EventDispatcher(new DependencyInversionListenerFactory($this->container))
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