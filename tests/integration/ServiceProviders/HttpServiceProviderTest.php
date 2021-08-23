<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Tests\stubs\TestApp;
use Snicco\Http\HttpKernel;
use Snicco\Http\Redirector;
use Tests\FrameworkTestCase;
use RKA\Middleware\IpAddress;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\AbstractRedirector;
use Psr\Http\Server\MiddlewareInterface;

class HttpServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_kernel_can_be_resolved_correctly()
    {
        $this->bootApp();
        $this->assertInstanceOf(HttpKernel::class, TestApp::resolve(HttpKernel::class));
    }
    
    /** @test */
    public function the_response_factory_can_be_resolved()
    {
        $this->bootApp();
        $this->assertInstanceOf(ResponseFactory::class, TestApp::resolve(ResponseFactory::class));
    }
    
    /** @test */
    public function the_redirector_can_be_resolved()
    {
        $this->bootApp();
        $this->assertInstanceOf(Redirector::class, TestApp::resolve(AbstractRedirector::class));
    }
    
    /** @test */
    public function the_trusted_proxies_middleware_is_bound()
    {
        $this->bootApp();
        /** @var IpAddress $middleware */
        $middleware = $this->app->resolve(IpAddress::class);
        $this->assertInstanceOf(IpAddress::class, $middleware);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
        
    }
    
    /** @test */
    public function checking_for_proxies_is_disabled_by_default()
    {
        
        $this->bootApp();
        /** @var IpAddress $middleware */
        $middleware = $this->app->resolve(IpAddress::class);
        
        $class = new \ReflectionClass($middleware);
        $property = $class->getProperty('checkProxyHeaders');
        $property->setAccessible(true);
        $value = $property->getValue($middleware);
        
        $this->assertFalse($value);
        
    }
    
    /** @test */
    public function checking_for_proxies_can_be_enabled()
    {
        
        $this->withAddedConfig('proxies.check', true);
        $this->withAddedConfig('proxies.trust', ['127.0.0.1']);
        $this->withAddedConfig('proxies.headers', ['foobar']);
        $this->bootApp();
        /** @var IpAddress $middleware */
        $middleware = $this->app->resolve(IpAddress::class);
        
        $class = new \ReflectionClass($middleware);
        $property = $class->getProperty('checkProxyHeaders');
        $property->setAccessible(true);
        $value = $property->getValue($middleware);
        
        $this->assertTrue($value);
        
    }
    
}

