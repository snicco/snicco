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
use Snicco\Core\Http\Responses\DelegatedResponse;
use Snicco\Core\Middleware\Core\RoutingMiddleware;
use Snicco\Core\EventDispatcher\Events\ResponseSent;
use Snicco\Core\Middleware\Core\AllowMatchingAdminRoutes;
use Snicco\Core\Middleware\Core\EvaluateResponseAbstractMiddleware;

/**
 * @todo The kernel should not send the response.
 */
final class HttpKernel
{
    
    private MiddlewarePipeline $pipeline;
    
    private array $core_middleware = [
        
        // @todo
        
        MethodOverride::class,
        
        // @todo This middleware should be configurable for route types.
        EvaluateResponseAbstractMiddleware::class,
        
        // @todo this middleware should be configurable for web/admin routes only.
        ShareCookies::class,
        
        // @todo Only for admin routes
        AllowMatchingAdminRoutes::class,
        
        // @todo this should not be a middleware at all.
        //OutputBufferAbstractMiddleware::class,
        
        RoutingMiddleware::class,
        
        RouteRunner::class,
    
    ];
    
    private ResponseEmitter $emitter;
    
    private Dispatcher $event_dispatcher;
    
    public function __construct(MiddlewarePipeline $pipeline, ResponseEmitter $emitter, Dispatcher $event_dispatcher)
    {
        $this->pipeline = $pipeline;
        $this->emitter = $emitter;
        $this->event_dispatcher = $event_dispatcher;
    }
    
    public function handle(Request $request) :Response
    {
        $response = $this->run($request);
        
        return $this->sendResponse($response, $request);
    }
    
    private function run(Request $request) :Response
    {
        return $this->pipeline->send($request)
                              ->through($this->gatherMiddleware($request))
                              ->run();
    }
    
    private function gatherMiddleware(Request $request) :array
    {
        //if ( ! $request->isToAdminDashboard()) {
        //    Arr::pullByValue(OutputBufferAbstractMiddleware::class, $this->core_middleware);
        //}
        
        return $this->core_middleware;
    }
    
    private function sendResponse(Response $response, Request $request) :Response
    {
        if ($response instanceof NullResponse) {
            return $response;
        }
        
        $response = $this->emitter->prepare($response, $request);
        
        if ($response instanceof DelegatedResponse) {
            $this->emitter->emitHeaders($response,);
            return $response;
        }
        
        $this->emitter->emit($response);
        
        $this->event_dispatcher->dispatch(new ResponseSent($response, $request));
        
        return $response;
    }
    
}

