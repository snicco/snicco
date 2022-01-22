<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use Closure;
use LogicException;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\EventDispatcher\Contracts\Dispatcher;
use Snicco\Component\HttpRouting\Middleware\MethodOverride;
use Snicco\Component\HttpRouting\Middleware\Internal\RouteRunner;
use Snicco\Component\HttpRouting\Http\Exceptions\RequestHasNoType;
use Snicco\Component\HttpRouting\Middleware\Internal\PrepareResponse;
use Snicco\Component\HttpRouting\Middleware\Internal\RoutingMiddleware;
use Snicco\Component\HttpRouting\Middleware\Internal\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\Internal\MiddlewareBlueprint;

final class HttpKernel
{
    
    private const CORE_MIDDLEWARE = [
        PrepareResponse::class,
        // MethodOverride needs to be in the kernel. It can be used as a route middleware.
        // As the route would never match to retrieve the middleware in the first place.
        MethodOverride::class,
        RoutingMiddleware::class,
        RouteRunner::class,
    ];
    
    private MiddlewarePipeline $pipeline;
    
    private Dispatcher $event_dispatcher;
    
    // @todo Use the dispatcher to send some events related to handling the request.
    public function __construct(MiddlewarePipeline $pipeline, Dispatcher $event_dispatcher)
    {
        $this->pipeline = $pipeline;
        $this->event_dispatcher = $event_dispatcher;
    }
    
    public function handle(Request $request) :Response
    {
        $this->validateRequest($request);
        
        $middleware = array_map(
            fn($middleware) => new MiddlewareBlueprint($middleware),
            self::CORE_MIDDLEWARE
        );
        
        return $this->pipeline->send($request)
                              ->through($middleware)
                              ->then($this->handleExhaustedMiddlewareStack());
    }
    
    // This should never happen.
    private function handleExhaustedMiddlewareStack() :Closure
    {
        return function (Request $request) {
            throw new RuntimeException(
                sprintf(
                    'Middleware stack returned no response for request [%s].',
                    (string) $request->getUri()
                )
            );
        };
    }
    
    private function validateRequest(Request $request)
    {
        try {
            // This will throw an exception if no type is set.
            $request->isToFrontend();
        } catch (RequestHasNoType $e) {
            throw new LogicException(
                'The HttpKernel tried to handle a request without a declared type. This is not allowed.'
            );
        }
    }
    
}

