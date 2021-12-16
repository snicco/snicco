<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Tests\Core\MiddlewareTestCase;
use Snicco\Core\Middleware\Core\MethodOverride;

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
        $request = $this->frontendRequest('POST')->withParsedBody([
            '_method' => 'PUT',
        ]);
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('PUT', $this->receivedRequest()->getMethod());
    }
    
    /** @test */
    public function the_method_cant_be_overwritten_for_anything_but_post_requests()
    {
        $request = $this->frontendRequest('GET')->withParsedBody([
            '_method' => 'PUT',
        ]);
        
        $response = $this->runMiddleware($this->middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $this->assertSame('GET', $this->receivedRequest()->getMethod());
    }
    
}