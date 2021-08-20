<?php

declare(strict_types=1);

namespace Snicco\Http;

use Snicco\Routing\Pipeline;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ServiceProvider;
use Snicco\Contracts\AbstractRedirector;

class HttpServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindKernel();
        
        $this->bindRedirector();
        
    }
    
    private function bindKernel()
    {
        $this->container->singleton(HttpKernel::class, function () {
            $kernel = new HttpKernel(
                $this->container->make(Pipeline::class),
                $this->container->make(ResponseEmitter::class),
            );
            
            if ($this->config->get('middleware.disabled', false)) {
                return $kernel;
            }
            
            if ($this->config->get('middleware.always_run_global', false)) {
                $kernel->alwaysWithGlobalMiddleware(
                    $this->config->get('middleware.groups.global', [])
                );
            }
            
            $kernel->withPriority($this->config->get('middleware.priority', []));
            
            return $kernel;
        });
    }
    
    private function bindRedirector()
    {
        $this->container->singleton(
            AbstractRedirector::class,
            fn() => $this->container->make(Redirector::class)
        );
    }
    
    public function bootstrap() :void
    {
        $this->bindRequestEndpoint();
    }
    
    private function bindRequestEndpoint()
    {
        $request = $this->currentRequest();
        
        if ($endpoints = $this->config->get('routing.api.endpoints', false)) {
            $this->current_request = $request->withAttribute('_api.endpoints', $endpoints);
            $this->container->instance(Request::class, $this->current_request);
        }
        
        if ($this->current_request->isApiEndPoint()) {
            $this->config->set('_request_endpoint', 'api');
            
            return;
        }
        
        if ($this->current_request->isWpAjax()) {
            $this->config->set('_request_endpoint', 'wp_ajax');
            
            return;
        }
        
        if ($this->current_request->isWpAdmin()) {
            $this->config->set('_request_endpoint', 'wp_admin');
            
            return;
        }
        
        $this->config->set('_request_endpoint', 'frontend');
    }
    
}
