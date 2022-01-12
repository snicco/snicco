<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Middleware\ShareCookies;
use Snicco\Core\Middleware\MethodOverride;
use Snicco\Core\Middleware\MustMatchRoute;
use Snicco\Core\Middleware\Internal\RouteRunner;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Core\Middleware\Internal\PrepareResponse;
use Snicco\Core\Middleware\Internal\RoutingMiddleware;
use Snicco\Core\Middleware\Internal\MiddlewarePipeline;

/**
 * @todo The kernel should not send the response.
 */
final class HttpKernel
{
    
    private MiddlewarePipeline $pipeline;
    
    private array $core_middleware = [
        
        PrepareResponse::class,
        
        MethodOverride::class,
        
        // @todo This middleware should be configurable for route types.
        MustMatchRoute::class,
        
        // @todo this middleware should be configurable for web/admin routes only.
        ShareCookies::class,
        
        // @todo this should not be a middleware at all.
        //OutputBufferAbstractMiddleware::class,
        
        RoutingMiddleware::class,
        
        RouteRunner::class,
    
    ];
    
    private Dispatcher $event_dispatcher;
    
    // @todo Use the dispatcher to send some events related to handling the request.
    public function __construct(MiddlewarePipeline $pipeline, Dispatcher $event_dispatcher)
    {
        $this->pipeline = $pipeline;
        $this->event_dispatcher = $event_dispatcher;
    }
    
    public function handle(Request $request) :Response
    {
        return $this->pipeline->send($request)
                              ->through($this->core_middleware)
                              ->run();
    }
    
}

