<?php

declare(strict_types=1);

namespace Snicco\Middleware;

use Snicco\Routing\Pipeline;
use Snicco\Http\ResponseEmitter;
use Snicco\Contracts\ServiceProvider;
use Snicco\Middleware\Core\RouteRunner;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Middleware\Core\OpenRedirectProtection;
use Snicco\Middleware\Core\OutputBufferMiddleware;
use Snicco\Middleware\Core\EvaluateResponseMiddleware;

class MiddlewareServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        
        $this->bindConfig();
        
        $this->bindMiddlewareStack();
        
        $this->bindEvaluateResponseMiddleware();
        
        $this->bindRouteRunnerMiddleware();
        
        $this->bindTrailingSlash();
        
        $this->bindWww();
        
        $this->bindOpenRedirectProtection();
        
        $this->bindOutputBufferMiddleware();
        
    }
    
    function bootstrap() :void
    {
        //
    }
    
    private function bindConfig()
    {
        $this->config->extend('middleware.aliases', [
            
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'can' => Authorize::class,
            'json' => JsonPayload::class,
            'robots' => NoRobots::class,
            'secure' => Secure::class,
            'signed' => ValidateSignature::class,
        
        ]);
        
        $this->config->extend('middleware.groups', [
            
            'global' => [],
            'web' => [],
            'ajax' => [],
            'admin' => [],
        
        ]);
        
        $this->config->extend(
            'middleware.priority',
            [Secure::class, Www::class, TrailingSlash::class,]
        );
        $this->config->extend('middleware.always_run_global', false);
    }
    
    private function bindMiddlewareStack()
    {
        $this->container->singleton(MiddlewareStack::class, function () {
            
            $stack = new MiddlewareStack();
            
            if ($this->config->get('middleware.disabled', false)) {
                $stack->disableAllMiddleware();
                return $stack;
            }
            
            foreach ($this->config->get('middleware.groups') as $name => $middleware) {
                
                $stack->withMiddlewareGroup($name, $middleware);
                
            }
            
            $stack->middlewarePriority($this->config->get('middleware.priority', []));
            $stack->middlewareAliases($this->config->get('middleware.aliases', []));
            
            return $stack;
            
        });
    }
    
    private function bindEvaluateResponseMiddleware()
    {
        $this->container->singleton(EvaluateResponseMiddleware::class, function () {
            
            return new EvaluateResponseMiddleware(
                $this->config->get('routing.must_match_web_routes', false)
            );
            
        });
    }
    
    private function bindRouteRunnerMiddleware()
    {
        $this->container->singleton(RouteRunner::class, function () {
            
            return new RouteRunner(
                $this->container,
                $this->container->make(Pipeline::class),
                $this->container->make(MiddlewareStack::class)
            );
            
        });
    }
    
    private function bindTrailingSlash()
    {
        $this->container->singleton(TrailingSlash::class, fn() => new TrailingSlash(
            $this->withSlashes()
        ));
    }
    
    private function bindWww()
    {
        $this->container->singleton(Www::class, fn() => new Www(
            $this->siteUrl()
        ));
    }
    
    private function bindOpenRedirectProtection()
    {
        $this->container->singleton(
            OpenRedirectProtection::class,
            fn() => new OpenRedirectProtection(
                $this->siteUrl()
            )
        );
    }
    
    private function bindOutputBufferMiddleware()
    {
        $this->container->singleton(OutputBufferMiddleware::class, function () {
            
            return new OutputBufferMiddleware(
                $this->container->make(ResponseEmitter::class),
                $this->container->make(StreamFactoryInterface::class)
            );
            
        });
    }
    
}