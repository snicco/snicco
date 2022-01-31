<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use Snicco\Component\HttpRouting\Http\MethodOverride;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;

class MethodOverrideTest extends MiddlewareTestCase
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
        $request = $this->frontendRequest('/', [], 'POST')->withParsedBody([
            '_method' => 'PUT',
        ]);
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('PUT', $this->getReceivedRequest()->getMethod());
    }
    
    /** @test */
    public function the_method_cant_be_overwritten_for_anything_but_post_requests()
    {
        $request = $this->frontendRequest('/foo')->withParsedBody([
            '_method' => 'PUT',
        ]);
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('GET', $this->getReceivedRequest()->getMethod());
    }
    
    /** @test */
    public function the_method_can_be_overwritten_with_the_method_override_header()
    {
        $request = $this->frontendRequest('/foo', [], 'POST')
                        ->withHeader('X-HTTP-Method-Override', 'PUT');
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('PUT', $this->getReceivedRequest()->getMethod());
        
        $request = $this->frontendRequest('/foo')
                        ->withHeader('X-HTTP-Method-Override', 'PUT');
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('GET', $this->getReceivedRequest()->getMethod());
    }
    
}