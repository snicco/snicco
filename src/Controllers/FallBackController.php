<?php

declare(strict_types=1);

namespace Snicco\Controllers;

use Closure;
use Snicco\Routing\Route;
use Snicco\Http\Controller;
use Snicco\Routing\Pipeline;
use Snicco\Http\Psr7\Request;
use Snicco\Middleware\MiddlewareStack;
use Snicco\Http\Responses\NullResponse;
use Psr\Http\Message\ResponseInterface;
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
    private Closure         $respond_with;
    
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
            
            $this->respond_with = $this->runRoute($route);
            $route->instantiateAction();
            $middleware = $this->middleware_stack->createFor($route, $request);
            
        }
        else {
            
            $this->respond_with = $this->nonMatchingRoute();
            $middleware = $this->middlewareForRequestWithoutRoute($request);
            
        }
        
        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then(function (Request $request) {
                
                $response = call_user_func($this->respond_with, $request);
                
                return $this->response_factory->toResponse($response);
                
            });
        
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
                : $this->response_factory->null();
            
        };
    }
    
    private function middlewareForRequestWithoutRoute(Request $request) :array
    {
        $groups = is_callable($this->fallback_handler)
            ? ['global', 'web',]
            : ($this->withWebMiddlewareGlobally($request) ? ['web'] : []);
        
        return $this->middleware_stack->onlyGroups($groups, $request);
    }
    
    private function withWebMiddlewareGlobally(Request $request)
    {
        // If this request attribute is true we know that global middleware has already
        // been run in the kernel which means "always_run_global" has been set to true in the config.
        return $request->getAttribute('global_middleware_run', false);
    }
    
    public function delegateToWordPress() :NullResponse
    {
        return $this->response_factory->null();
    }
    
    public function setFallbackHandler(callable $fallback_handler)
    {
        $this->fallback_handler = $fallback_handler;
    }
    
}