<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use Snicco\Component\HttpRouting\Middleware\TrailingSlash;
use Snicco\Component\HttpRouting\Tests\InternalMiddlewareTestCase;

class TrailingSlashTest extends InternalMiddlewareTestCase
{
    
    public function testRedirectNoSlashToTrailingSlash()
    {
        $request = $this->frontendRequest('https://foo.com/bar');
        
        $response = $this->runMiddleware(new TrailingSlash(true), $request);
        
        $response->assertNextMiddlewareNotCalled();
        $response->assertRedirect();
        $response->assertStatus(301);
        
        $response->assertRedirectPath('/bar/');
    }
    
    /** @test */
    public function testRedirectSlashToNoSlash()
    {
        $request = $this->frontendRequest('https://foo.com/bar/');
        
        $response = $this->runMiddleware(new TrailingSlash(false), $request);
        
        $response->assertNextMiddlewareNotCalled();
        $response->assertRedirect('/bar', 301);
    }
    
    public function testNoRedirectIfSlashesAreCorrect()
    {
        $request = $this->frontendRequest('https://foo.com/bar');
        
        $response = $this->runMiddleware(new TrailingSlash(false), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertOk();
    }
    
}
