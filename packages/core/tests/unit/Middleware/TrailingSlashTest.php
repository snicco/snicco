<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Tests\Core\MiddlewareTestCase;
use Snicco\Core\Middleware\TrailingSlash;

class TrailingSlashTest extends MiddlewareTestCase
{
    
    public function testRedirectNoSlashToTrailingSlash()
    {
        $request = $this->frontendRequest('GET', 'https://foo.com/bar');
        
        $response = $this->runMiddleware(new TrailingSlash(true), $request);
        
        $response->assertNextMiddlewareNotCalled();
        $response->assertRedirect();
        $response->assertStatus(301);
        
        $response->assertRedirectPath('/bar/');
    }
    
    /** @test */
    public function testRedirectSlashToNoSlash()
    {
        $request = $this->frontendRequest('GET', 'https://foo.com/bar/');
        
        $response = $this->runMiddleware(new TrailingSlash(false), $request);
        
        $response->assertNextMiddlewareNotCalled();
        $response->assertRedirect('/bar', 301);
    }
    
    public function testNoRedirectIfSlashesAreCorrect()
    {
        $request = $this->frontendRequest('GET', 'https://foo.com/bar');
        
        $response = $this->runMiddleware(new TrailingSlash(false), $request);
        
        $response->assertNextMiddlewareCalled();
        $response->assertOk();
    }
    
}
