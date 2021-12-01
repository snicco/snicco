<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Closure;
use Throwable;
use LogicException;
use Snicco\Support\Arr;
use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use InvalidArgumentException;
use Snicco\Http\Psr7\Response;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\ExceptionHandler;
use Snicco\Factories\MiddlewareFactory;
use Psr\Http\Server\MiddlewareInterface;

class Pipeline
{
    
    private ExceptionHandler  $error_handler;
    private Request           $request;
    private ResponseFactory   $response_factory;
    private array             $middleware = [];
    private MiddlewareFactory $middleware_factory;
    
    public function __construct(MiddlewareFactory $middleware_factory, ExceptionHandler $error_handler, ResponseFactory $response_factory)
    {
        $this->middleware_factory = $middleware_factory;
        $this->error_handler = $error_handler;
        $this->response_factory = $response_factory;
    }
    
    public function send(Request $request) :Pipeline
    {
        $this->request = $request;
        return $this;
    }
    
    /**
     * Set the array of middleware.
     * Accepted: function ($request, Closure $next), Middleware::class , [Middleware ,
     * 'config_value'
     * Middleware classes must implement Psr\Http\Server\MiddlewareInterface
     */
    public function through(array $middleware) :Pipeline
    {
        $this->middleware = $this->normalizeMiddleware($middleware);
        return $this;
    }
    
    public function then(Closure $request_handler) :Response
    {
        $this->middleware[] = [new Delegate($request_handler), []];
        
        return $this->run($this->buildMiddlewareStack());
    }
    
    public function run($stack = null) :Response
    {
        $stack = $stack ?? $this->buildMiddlewareStack();
        
        return $stack->handle($this->request);
    }
    
    private function normalizeMiddleware(array $middleware) :array
    {
        $middleware = array_map(function ($middleware) {
            if ($middleware instanceof Closure) {
                return new Delegate($middleware);
            }
            
            return $middleware;
        }, $middleware);
        
        $middleware = array_map(function ($middleware) {
            $middleware = Arr::wrap($middleware);
            
            if ( ! in_array(MiddlewareInterface::class, class_implements($middleware[0]))) {
                throw new InvalidArgumentException(
                    "Unsupported middleware type: {$middleware[0]})"
                );
            }
            
            return $middleware;
        }, $middleware);
        
        return array_map(function ($middleware) {
            return $this->getMiddlewareAndParams($middleware);
        }, $middleware);
    }
    
    /**
     * @param  array|string|object  $middleware_blueprint
     *
     * @return array<string|object,array>
     */
    private function getMiddlewareAndParams($middleware_blueprint) :array
    {
        if (is_object($middleware_blueprint)) {
            return [$middleware_blueprint, []];
        }
        
        if (is_string($middleware_blueprint)) {
            return [$middleware_blueprint, []];
        }
        
        $middleware_class = array_shift($middleware_blueprint);
        
        $constructor_args = $middleware_blueprint;
        
        return [$middleware_class, $constructor_args];
    }
    
    private function buildMiddlewareStack() :Delegate
    {
        return $this->nextMiddleware();
    }
    
    private function nextMiddleware() :Delegate
    {
        if ($this->middleware === []) {
            return new Delegate(function () {
                throw new LogicException(
                    "Unresolved request: middleware stack exhausted with no result"
                );
            });
        }
        
        return new Delegate(function (Request $request) {
            try {
                return $this->resolveNextMiddleware($request);
            } catch (Throwable $e) {
                $this->error_handler->report($e, $request);
                
                return $this->error_handler->toHttpResponse($e, $request);
            }
        });
    }
    
    private function resolveNextMiddleware(Request $request) :Response
    {
        [$middleware, $arguments_from_route_definition] = array_shift($this->middleware);
        
        if ($middleware instanceof MiddlewareInterface) {
            $this->giveResponseFactory($middleware);
            return $middleware->process($request, $this->nextMiddleware());
        }
        
        $arguments_from_route_definition = $this->stringToBool(
            $arguments_from_route_definition
        );
        
        $middleware_instance = $this->middleware_factory->create(
            $middleware,
            $arguments_from_route_definition
        );
        
        $this->giveResponseFactory($middleware_instance);
        
        /** @var Response $response */
        $response = $middleware_instance->process($request, $this->nextMiddleware());
        return $response;
    }
    
    private function stringToBool(array $constructor_args) :array
    {
        return array_map(function ($value) {
            if ( ! is_string($value)) {
                return $value;
            }
            
            if (strtolower($value) === 'true') {
                return true;
            }
            if (strtolower($value) === 'false') {
                return false;
            }
            
            return $value;
        }, $constructor_args);
    }
    
    private function giveResponseFactory(MiddlewareInterface $middleware)
    {
        if (method_exists($middleware, 'setResponseFactory')) {
            $middleware->setResponseFactory($this->response_factory);
        }
    }
    
}