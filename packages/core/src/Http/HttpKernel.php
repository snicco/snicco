<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use Snicco\Support\Arr;
use Snicco\Core\Routing\Pipeline;
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
use Snicco\Core\Middleware\Core\SetRequestAttributes;
use Snicco\Core\Middleware\Core\OutputBufferMiddleware;
use Snicco\Core\Middleware\Core\EvaluateResponseMiddleware;
use Snicco\Core\Middleware\Core\AllowMatchingAdminAndAjaxRoutes;

class HttpKernel
{
    
    private Pipeline $pipeline;
    
    private array $core_middleware = [
        SetRequestAttributes::class,
        MethodOverride::class,
        EvaluateResponseMiddleware::class,
        ShareCookies::class,
        AllowMatchingAdminAndAjaxRoutes::class,
        OutputBufferMiddleware::class,
        RoutingMiddleware::class,
        RouteRunner::class,
    ];
    
    private ResponseEmitter $emitter;
    
    private Dispatcher $event_dispatcher;
    
    public function __construct(Pipeline $pipeline, ResponseEmitter $emitter, Dispatcher $event_dispatcher)
    {
        $this->pipeline = $pipeline;
        $this->emitter = $emitter;
        $this->event_dispatcher = $event_dispatcher;
    }
    
    public function run(Request $request) :Response
    {
        $response = $this->handle($request);
        
        return $this->sendResponse($response, $request);
    }
    
    private function handle(Request $request) :Response
    {
        return $this->pipeline->send($request)
                              ->through($this->gatherMiddleware($request))
                              ->run();
    }
    
    private function gatherMiddleware(Request $request) :array
    {
        if ( ! $request->isWpAdmin()) {
            Arr::pullByValue(OutputBufferMiddleware::class, $this->core_middleware);
        }
        
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

