<?php

declare(strict_types=1);

namespace Tests\HttpRouting\integration\Http;

use ReflectionClass;
use RKA\Middleware\IpAddress;
use Snicco\HttpRouting\Http\Redirector;
use Snicco\HttpRouting\Http\HttpKernel;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\HttpRouting\Http\ResponseFactory;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\HttpRouting\Http\ResponsePostProcessor;
use Snicco\HttpRouting\Http\DefaultResponseFactory;

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
        $this->assertInstanceOf(
            DefaultResponseFactory::class,
            TestApp::resolve(ResponseFactory::class)
        );
        
        $this->assertInstanceOf(
            DefaultResponseFactory::class,
            TestApp::resolve(DefaultResponseFactory::class)
        );
    }
    
    /** @test */
    public function the_redirector_can_be_resolved()
    {
        $this->bootApp();
        $this->assertInstanceOf(Redirector::class, TestApp::resolve(Redirector::class));
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
        
        $class = new ReflectionClass($middleware);
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
        
        $class = new ReflectionClass($middleware);
        $property = $class->getProperty('checkProxyHeaders');
        $property->setAccessible(true);
        $value = $property->getValue($middleware);
        
        $this->assertTrue($value);
    }
    
    /** @test */
    public function the_response_post_processor_is_bound()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            ResponsePostProcessor::class,
            $this->app[ResponsePostProcessor::class]
        );
    }
    
}

