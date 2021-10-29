<?php

declare(strict_types=1);

namespace Tests\unit\Middleware;

use Tests\UnitTest;
use Snicco\Http\Delegate;
use Tests\stubs\TestRequest;
use Snicco\Middleware\DefaultHeaders;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreatePsr17Factories;
use Tests\helpers\CreateRouteCollection;

class DefaultHeadersTest extends UnitTest
{
    
    use CreatePsr17Factories;
    use CreateUrlGenerator;
    use CreateRouteCollection;
    
    /** @test */
    public function all_headers_are_added_to_the_response()
    {
        
        $next = new Delegate(function ($request) {
            return $this->createResponseFactory()->make();
        });
        
        $response = (new DefaultHeaders(['foo' => 'bar', 'baz' => 'biz']))->handle(
            TestRequest::from('GET', 'foo'),
            $next
        );
        
        $this->assertSame('bar', $response->getHeaderLine('foo'));
        $this->assertSame('biz', $response->getHeaderLine('baz'));
        
    }
    
    /** @test */
    public function x_frame_headers_are_added_by_default()
    {
        
        $next = new Delegate(function ($request) {
            return $this->createResponseFactory()->make();
        });
        
        $response = (new DefaultHeaders())->handle(
            TestRequest::from('GET', 'foo'),
            $next
        );
        
        $this->assertSame(
            'SAMEORIGIN',
            $response->getHeaderLine('X-Frame-Options')
        );
        
    }
    
    /** @test */
    public function header_values_are_not_overwritten()
    {
        
        $next = new Delegate(function () {
            return $this->createResponseFactory()->make()->withHeader('foo', 'bar');
        });
        
        $response = (new DefaultHeaders(['foo' => 'baz',]))->handle(
            TestRequest::from('GET', 'foo'),
            $next
        );
        
        $this->assertSame(
            'bar',
            $response->getHeaderLine('foo'),
            "Default header had prority over route header."
        );
        
    }
    
}