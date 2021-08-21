<?php

declare(strict_types=1);

namespace Snicco\Http;

use Snicco\Routing\Pipeline;
use Snicco\Contracts\ServiceProvider;
use Snicco\Contracts\AbstractRedirector;

class HttpServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindKernel();
        
        $this->bindRedirector();
        
    }
    
    public function bootstrap() :void
    {
        //
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
    
}
