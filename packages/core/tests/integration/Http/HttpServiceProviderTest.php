<?php

declare(strict_types=1);

namespace Tests\Core\integration\Http;

use ReflectionClass;
use Snicco\Http\HttpKernel;
use Snicco\Http\MethodField;
use RKA\Middleware\IpAddress;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\Redirector;
use Snicco\Http\StatelessRedirector;
use Snicco\Http\ResponsePostProcessor;
use Psr\Http\Server\MiddlewareInterface;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;

class HttpServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_method_field_can_be_resolved()
    {
        $this->bootApp();
        $this->assertInstanceOf(MethodField::class, $this->app->resolve(MethodField::class));
    }
    
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
        $this->assertInstanceOf(StatelessRedirector::class, TestApp::resolve(Redirector::class));
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

