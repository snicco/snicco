<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Http\Responses\NullResponse;
use Snicco\Core\Middleware\Core\RouteRunner;
use Snicco\Core\Middleware\Core\ShareCookies;
use Snicco\Core\Middleware\Core\MethodOverride;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Core\Middleware\Core\PrepareResponse;
use Snicco\Core\Middleware\Core\RoutingMiddleware;
use Snicco\Core\Middleware\Core\AllowMatchingAdminRoutes;
use Snicco\Core\Middleware\Core\EvaluateResponseAbstractMiddleware;

/**
 * @todo The kernel should not send the response.
 */
final class HttpKernel
{
    
    private MiddlewarePipeline $pipeline;
    
    private array $core_middleware = [
        
        // @todo Prepare the response here.
        PrepareResponse::class,
        
        MethodOverride::class,
        
        // @todo This middleware should be configurable for route types.
        EvaluateResponseAbstractMiddleware::class,
        
        // @todo this middleware should be configurable for web/admin routes only.
        ShareCookies::class,
        
        AllowMatchingAdminRoutes::class,
        
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

