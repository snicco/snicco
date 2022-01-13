<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use Closure;
use RuntimeException;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Middleware\MethodOverride;
use Snicco\Core\Middleware\Internal\TagRequest;
use Snicco\Core\Middleware\Internal\RouteRunner;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Core\Middleware\Internal\PrepareResponse;
use Snicco\Core\Middleware\Internal\RoutingMiddleware;
use Snicco\Core\Middleware\Internal\MiddlewarePipeline;
use Snicco\Core\Middleware\Internal\MiddlewareBlueprint;

/**
 * @todo The kernel should not send the response.
 */
final class HttpKernel
{
    
    private const CORE_MIDDLEWARE = [
        TagRequest::class,
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
    
}

