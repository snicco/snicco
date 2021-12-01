<?php

declare(strict_types=1);

namespace Snicco\Http;

use Snicco\Support\Arr;
use Snicco\Routing\Pipeline;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Http\Responses\NullResponse;
use Snicco\Middleware\Core\RouteRunner;
use Snicco\Middleware\Core\ShareCookies;
use Snicco\Middleware\Core\MethodOverride;
use Snicco\Http\Responses\DelegatedResponse;
use Snicco\Middleware\Core\RoutingMiddleware;
use Snicco\EventDispatcher\Events\ResponseSent;
use Snicco\Middleware\Core\SetRequestAttributes;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Middleware\Core\OutputBufferMiddleware;
use Snicco\Middleware\Core\AppendSpecialPathSuffix;
use Snicco\Middleware\Core\SubstituteRouteBindings;
use Snicco\Middleware\Core\EvaluateResponseMiddleware;

class HttpKernel
{
    
    private Pipeline $pipeline;
    
    private array $core_middleware = [
        SetRequestAttributes::class,
        MethodOverride::class,
        EvaluateResponseMiddleware::class,
        ShareCookies::class,
        AppendSpecialPathSuffix::class,
        OutputBufferMiddleware::class,
        RoutingMiddleware::class,
        SubstituteRouteBindings::class,
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

