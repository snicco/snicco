<?php

declare(strict_types=1);

namespace Snicco\Middleware\Core;

use Closure;
use Snicco\Http\Delegate;
use Snicco\Routing\Route;
use Snicco\Routing\Pipeline;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Contracts\ContainerAdapter;
use Snicco\Contracts\Middleware;
use Snicco\Routing\RoutingResult;
use Snicco\Middleware\MiddlewareStack;
use Psr\Http\Message\ResponseInterface;

class RouteRunner extends Middleware
{
    
    private Pipeline         $pipeline;
    private MiddlewareStack  $middleware_stack;
    private ContainerAdapter $container;
    
    public function __construct(ContainerAdapter $container, Pipeline $pipeline, MiddlewareStack $middleware_stack)
    {
        $this->pipeline = $pipeline;
        $this->middleware_stack = $middleware_stack;
        $this->container = $container;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        
        $this->rebindRequest($request);
        
        $route_result = $request->routingResult();
        
        if ( ! $route = $route_result->route()) {
            
            return $this->delegateToWordPress($request);
            
        }
        
        // The Middleware Pipeline is created within the FallbackController::class
        // because we might have actually matched a route via WordPress conditional tags.
        if ($route->isFallback()) {
            
            return $this->runFallbackRouteController($route, $request);
            
        }
        
        $middleware = $this->middleware_stack->createForRoute($route);
        
        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then($this->runRoute($route_result));
        
    }
    
    private function rebindRequest(Request $request)
    {
        $this->container->instance(Request::class, $request);
    }
    
    private function runFallbackRouteController(Route $route, Request $request) :Response
    {
        return $this->response_factory->toResponse($route->run($request));
    }
    
    private function runRoute(RoutingResult $routing_result) :Closure
    {
        
        return function (Request $request) use ($routing_result) {
            
            $response = $routing_result->route()->run(
                $request,
                $routing_result->capturedUrlSegmentValues()
            );
            
            return $this->response_factory->toResponse($response);
            
        };
        
    }
    
    private function delegateToWordPress(Request $request) :Response
    {
        
        $middleware = $this->middleware_stack->createForRequestWithoutRoute($request);
        
        if ( ! count($middleware)) {
            return $this->response_factory->delegateToWP();
        }
        
        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then(fn() => $this->response_factory->delegateToWP());
        
    }
    
}