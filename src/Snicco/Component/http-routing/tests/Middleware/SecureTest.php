<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use Snicco\Component\HttpRouting\Middleware\Secure;
use Snicco\Component\HttpRouting\Tests\InternalMiddlewareTestCase;

final class SecureTest extends InternalMiddlewareTestCase
{
    
    /** @test */
    public function no_redirect_happen_on_local_environment()
    {
        $middleware = new Secure(true);
        
        $request = $this->frontendRequest('http://foobar.com');
        
        $response = $this->runMiddleware($middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }
    
    /** @test */
    public function http_request_are_redirected()
    {
        $middleware = new Secure();
        
        $request = $this->frontendRequest('http://foobar.com/foo/bar');
        
        $response = $this->runMiddleware($middleware, $request);
        
        $response->assertNextMiddlewareNotCalled();
        $response->psr()->assertRedirect('https://foobar.com/foo/bar', 301);
    }
    
    /** @test */
    public function https_requests_are_not_redirected()
    {
        $middleware = new Secure();
        
        $request = $this->frontendRequest('https://foobar.com/foo/bar');
        
        $response = $this->runMiddleware($middleware, $request);
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }
    
}