<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use Snicco\Component\HttpRouting\Middleware\MethodOverride;
use Snicco\Component\HttpRouting\Tests\InternalMiddlewareTestCase;

class MethodOverrideTest extends InternalMiddlewareTestCase
{
    
    private MethodOverride $middleware;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->middleware = new MethodOverride();
    }
    
    /** @test */
    public function the_method_can_be_overwritten_for_post_requests()
    {
        $request = $this->frontendRequest('POST')->withParsedBody([
            '_method' => 'PUT',
        ]);
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('PUT', $this->getReceivedRequest()->getMethod());
    }
    
    /** @test */
    public function the_method_cant_be_overwritten_for_anything_but_post_requests()
    {
        $request = $this->frontendRequest('GET')->withParsedBody([
            '_method' => 'PUT',
        ]);
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('GET', $this->getReceivedRequest()->getMethod());
    }
    
    /** @test */
    public function the_method_can_be_overwritten_with_the_method_override_header()
    {
        $request = $this->frontendRequest('POST', '/foo')
                        ->withHeader('X-HTTP-Method-Override', 'PUT');
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('PUT', $this->getReceivedRequest()->getMethod());
        
        $request = $this->frontendRequest('GET', '/foo')
                        ->withHeader('X-HTTP-Method-Override', 'PUT');
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('GET', $this->getReceivedRequest()->getMethod());
    }
    
    /** @test */
    public function the_middleware_behaviour_can_be_disabled()
    {
        $request = $this->frontendRequest('POST')->withParsedBody([
            '_method' => 'PUT',
        ]);
        
        $m = new MethodOverride(false);
        
        $response = $this->runMiddleware($m, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('POST', $this->getReceivedRequest()->getMethod());
    }
    
}