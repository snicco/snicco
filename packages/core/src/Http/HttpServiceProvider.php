<?php

declare(strict_types=1);

namespace Snicco\Http;

use RuntimeException;
use RKA\Middleware\IpAddress;
use Snicco\Contracts\Redirector;
use Snicco\Contracts\ServiceProvider;
use Snicco\EventDispatcher\Contracts\Dispatcher;

class HttpServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindRedirector();
        $this->bindResponsePostProcessor();
        $this->bindIpAddressMiddleware();
        $this->bindMethodField();
    }
    
    public function bootstrap() :void
    {
        //
    }
    
    private function bindRedirector()
    {
        $this->container->singleton(
            Redirector::class,
            fn() => $this->container->make(StatelessRedirector::class)
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
    
    private function bindResponsePostProcessor()
    {
        $this->container->singleton(ResponsePostProcessor::class, function () {
            return new ResponsePostProcessor(
                $this->container[Dispatcher::class],
                $this->app->isRunningUnitTest()
            );
        });
    }
    
    private function bindMethodField()
    {
        $this->container->singleton(MethodField::class, fn() => new MethodField(
            $this->appKey()
        ));
    }
    
}
