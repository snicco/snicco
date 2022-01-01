<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Closure;
use Snicco\Core\Routing\Route;
use Snicco\Core\Routing\Delegate;
use Snicco\Core\Routing\Pipeline;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Middleware\MiddlewareStack;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Factories\RouteActionFactory;

class RouteRunner extends AbstractMiddleware
{
    
    private Pipeline           $pipeline;
    private MiddlewareStack    $middleware_stack;
    private RouteActionFactory $factory;
    
    public function __construct(Pipeline $pipeline, MiddlewareStack $middleware_stack, RouteActionFactory $factory)
    {
        $this->pipeline = $pipeline;
        $this->middleware_stack = $middleware_stack;
        $this->factory = $factory;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ( ! $route = $request->route()) {
            return $this->delegateToWordPress($request);
        }
        
        $route->instantiateAction($this->factory);
        
        $middleware = $this->middleware_stack->createForRoute($route);
        
        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then($this->runRoute($route));
    }
    
    private function runRoute(Route $route) :Closure
    {
        return function (Request $request) use ($route) {
            return $this->respond()->toResponse(
                $route->run($request)
            );
        };
    }
    
    private function delegateToWordPress(Request $request) :Response
    {
        $middleware = $this->middleware_stack->createForRequestWithoutRoute($request);
        
        if ( ! count($middleware)) {
            return $this->respond()->delegateToWP();
        }
        
        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then(function () {
                return $this->respond()->delegateToWP();
            });
    }
    
}