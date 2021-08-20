<?php

declare(strict_types=1);

namespace Tests\helpers;

use Closure;
use Snicco\Routing\Router;
use Snicco\Http\HttpKernel;
use Snicco\Routing\Pipeline;
use Snicco\View\MethodField;
use Tests\stubs\TestRequest;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Snicco\Http\ResponseEmitter;
use Snicco\Events\IncomingRequest;
use Snicco\Events\IncomingWebRequest;
use Snicco\Middleware\MiddlewareStack;
use Snicco\Middleware\Core\RouteRunner;
use Snicco\Routing\RoutingServiceProvider;
use Snicco\Contracts\ErrorHandlerInterface;
use Tests\fixtures\Conditions\TrueCondition;
use Tests\fixtures\Middleware\BarMiddleware;
use Tests\fixtures\Middleware\BazMiddleware;
use Tests\fixtures\Middleware\FooMiddleware;
use Snicco\Contracts\AbstractRouteCollection;
use Tests\fixtures\Conditions\FalseCondition;
use Tests\fixtures\Conditions\MaybeCondition;
use Snicco\ExceptionHandling\NullErrorHandler;
use Tests\fixtures\Conditions\UniqueCondition;
use Tests\fixtures\Middleware\FooBarMiddleware;
use Tests\fixtures\Conditions\ConditionWithDependency;

/**
 * @internal
 */
trait CreateTestSubjects
{
    
    use CreateUrlGenerator;
    use CreateRouteCollection;
    use CreateRouteCollection;
    
    protected MiddlewareStack $middleware_stack;
    
    protected function createRoutes(Closure $routes, bool $force_trailing = false)
    {
        
        $this->routes = $this->newRouteCollection();
        
        $this->router = $this->newRouter($force_trailing);
        
        $routes();
        
        $this->router->loadRoutes();
        
    }
    
    protected function newRouter(bool $force_trailing = false) :Router
    {
        
        return new Router($this->container, $this->routes, $force_trailing);
        
    }
    
    protected function runAndAssertEmptyOutput(IncomingRequest $request)
    {
        
        $this->runAndAssertOutput('', $request);
        
    }
    
    protected function runAndAssertOutput($expected, IncomingRequest $request)
    {
        
        $this->assertSame(
            $expected,
            $actual = $this->runKernelAndGetOutput($request),
            "Expected output:[{$expected}] Received:['{$actual}']."
        );
        
    }
    
    protected function runKernelAndGetOutput(IncomingRequest $request, HttpKernel $kernel = null)
    {
        
        $kernel = $kernel ?? $this->newKernel();
        
        ob_start();
        $this->runKernel($request, $kernel);
        
        return ob_get_clean();
        
    }
    
    protected function newKernel(array $with_middleware = []) :HttpKernel
    {
        
        $this->container->instance(
            ErrorHandlerInterface::class,
            $error_handler = new NullErrorHandler()
        );
        $this->container->instance(AbstractRouteCollection::class, $this->routes);
        $this->container->instance(
            ResponseFactory::class,
            $factory = $this->createResponseFactory()
        );
        $this->container->instance(ContainerAdapter::class, $this->container);
        $this->container->instance(MethodField::class, new MethodField(TEST_APP_KEY));
        
        $middleware_stack = new MiddlewareStack();
        $middleware_stack->middlewareAliases([
            'foo' => FooMiddleware::class,
            'bar' => BarMiddleware::class,
            'baz' => BazMiddleware::class,
            'foobar' => FooBarMiddleware::class,
        ]);
        foreach ($with_middleware as $group_name => $middlewares) {
            
            $middleware_stack->withMiddlewareGroup($group_name, $middlewares);
            
        }
        
        $router_runner = new RouteRunner(
            $this->container,
            new Pipeline($this->container, $error_handler),
            $middleware_stack
        );
        
        $this->container->instance(RouteRunner::class, $router_runner);
        $this->container->instance(MiddlewareStack::class, $middleware_stack);
        $this->middleware_stack = $middleware_stack;
        
        return new HttpKernel(
            new Pipeline($this->container, $error_handler),
            new ResponseEmitter()
        );
        
    }
    
    protected function runKernel(IncomingRequest $request, HttpKernel $kernel = null)
    {
        
        $kernel = $kernel ?? $this->newKernel();
        $kernel->run($request);
        
    }
    
    protected function conditions() :array
    {
        
        return array_merge(RoutingServiceProvider::CONDITION_TYPES, [
            
            'true' => TrueCondition::class,
            'false' => FalseCondition::class,
            'maybe' => MaybeCondition::class,
            'unique' => UniqueCondition::class,
            'dependency_condition' => ConditionWithDependency::class,
        
        ]);
        
    }
    
    protected function webRequest($method, $path) :IncomingWebRequest
    {
        
        return new IncomingWebRequest(TestRequest::from($method, $path));
        
    }
    
}