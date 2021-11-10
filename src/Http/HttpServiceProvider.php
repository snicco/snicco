<?php

declare(strict_types=1);

namespace Snicco\Http;

use RuntimeException;
use Snicco\Routing\Pipeline;
use RKA\Middleware\IpAddress;
use Snicco\Contracts\ServiceProvider;
use Snicco\Contracts\AbstractRedirector;

class HttpServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindKernel();
        $this->bindRedirector();
        $this->bindIpAddressMiddleware();
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
    
    private function bindConfig()
    {
        
        if ( ! class_exists(IpAddress::class)) {
            return;
        }
        
        $this->config->extend('proxies.check', false);
        $this->config->extend('proxies.trust', []);
        $this->config->extend('proxies.headers', []);
        
    }
    
    private function bindIpAddressMiddleware()
    {
        $this->container->singleton(IpAddress::class, function () {
            
            $check = $this->config->get('proxies.check');
            $proxies = $this->config->get('proxies.trust');
            $headers = $this->config->get('proxies.headers');
            
            if ($check && empty($proxies)) {
                throw new RuntimeException('You have to configure trusted proxies.');
            }
            if ($check && empty($headers)) {
                throw new RuntimeException(
                    'You have to configure headers to extract the remote ip.'
                );
            }
            
            return new IpAddress($check, $proxies, 'ip_address', $headers);
            
        });
    }
    
}
