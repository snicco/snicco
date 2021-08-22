<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Closure;
use Throwable;
use LogicException;
use Snicco\Support\Arr;
use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionPayload\ReflectionPayload;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

use function collect;

class Pipeline
{
    
    private ExceptionHandler $error_handler;
    
    private ContainerAdapter $container;
    
    private Request          $request;
    
    private array            $middleware = [];
    
    private ResponseFactory  $response_factory;
    
    public function __construct(ContainerAdapter $container, ExceptionHandler $error_handler)
    {
        $this->container = $container;
        $this->error_handler = $error_handler;
        $this->response_factory = $this->container->make(ResponseFactory::class);
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
    
    public function then(Closure $request_handler) :ResponseInterface
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
        
        return collect($middleware)
            ->map(function ($middleware) {
                
                if ($middleware instanceof Closure) {
                    
                    return new Delegate($middleware);
                }
                
                return $middleware;
                
            })
            ->map(function ($middleware) {
                
                $middleware = Arr::wrap($middleware);
                
                if ( ! in_array(MiddlewareInterface::class, class_implements($middleware[0]))) {
                    
                    throw new ConfigurationException(
                        "Unsupported middleware type: {$middleware[0]})"
                    );
                    
                }
                
                return $middleware;
                
            })
            ->map(function ($middleware) {
                
                return $this->getMiddlewareAndParams($middleware);
                
            })
            ->all();
        
    }
    
    /**
     * @param  array|string|object  $middleware_blueprint
     *
     * @return array
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
                
                return $this->error_handler->transformToResponse($e, $request);
                
            }
            
        });
        
    }
    
    private function resolveNextMiddleware(Request $request) :Response
    {
        
        [$middleware, $constructor_args] = array_shift($this->middleware);
        
        if ($middleware instanceof MiddlewareInterface) {
            
            return $middleware->process($request, $this->nextMiddleware());
            
        }
        
        $constructor_args = $this->convertStringsToBooleans($constructor_args);
        
        $payload = new ReflectionPayload($middleware, $constructor_args);
        
        $middleware_instance = $this->container->make($middleware, $payload->build());
        
        if (method_exists($middleware_instance, 'setResponseFactory')) {
            
            $middleware_instance->setResponseFactory($this->response_factory);
            
        }
        
        return $middleware_instance->process($request, $this->nextMiddleware());
        
    }
    
    private function convertStringsToBooleans(array $constructor_args) :array
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
    
}