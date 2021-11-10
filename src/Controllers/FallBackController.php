<?php

declare(strict_types=1);

namespace Snicco\Controllers;

use Closure;
use Snicco\Routing\Route;
use Snicco\Http\Controller;
use Snicco\Routing\Pipeline;
use Snicco\Http\Psr7\Request;
use Snicco\Middleware\MiddlewareStack;
use Psr\Http\Message\ResponseInterface;
use Snicco\Http\Responses\DelegatedResponse;
use Snicco\Contracts\AbstractRouteCollection as Routes;

/**
 * This class is the default route handler for ALL routes that
 * do not have a URL-Constraint specified but instead rely on WordPress conditional tags.
 * We can't match these routes with FastRoute so this Controller will figure out if we
 * have a matching route.
 */
class FallBackController extends Controller
{
    
    /**
     * @var callable
     */
    private                 $fallback_handler;
    private Pipeline        $pipeline;
    private MiddlewareStack $middleware_stack;
    
    public function __construct(Pipeline $pipeline, MiddlewareStack $middleware_stack)
    {
        $this->pipeline = $pipeline;
        $this->middleware_stack = $middleware_stack;
    }
    
    public function handle(Request $request, Routes $routes) :ResponseInterface
    {
        
        $possible_routes = collect($routes->withWildCardUrl($request->getMethod()));
        
        /** @var Route $route */
        $route = $possible_routes->first(
            fn(Route $route) => $route->instantiateConditions()->satisfiedBy($request)
        );
        
        if ($route) {
            
            $route->instantiateAction();
            $handler = $this->runRoute($route);
            $middleware = $this->middleware_stack->createForRoute($route);
            
        }
        else {
            
            $handler = $this->nonMatchingRoute();
            $middleware = $this->middleware_stack->createForRequestWithoutRoute(
                $request,
                is_callable($this->fallback_handler)
            );
            
        }
        
        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then(function (Request $request) use ($handler) {
                
                return $this->response_factory->toResponse(
                    call_user_func($handler, $request)
                );
                
            });
        
    }
    
    public function delegateToWordPress() :DelegatedResponse
    {
        return $this->response_factory->delegateToWP();
    }
    
    public function setFallbackHandler(callable $fallback_handler)
    {
        $this->fallback_handler = $fallback_handler;
    }
    
    private function runRoute(Route $route) :Closure
    {
        return fn(Request $request) => $route->run($request);
    }
    
    private function nonMatchingRoute() :Closure
    {
        return function (Request $request) {
            
            return ($this->fallback_handler)
                ? call_user_func($this->fallback_handler, $request)
                : $this->response_factory->delegateToWP();
            
        };
    }
    
}