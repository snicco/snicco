<?php

declare(strict_types=1);

namespace Tests\HttpRouting\unit\Middleware;

use Snicco\HttpRouting\Http\Psr7\Response;
use Tests\HttpRouting\InternalMiddlewareTestCase;
use Snicco\HttpRouting\Middleware\DefaultHeaders;

class DefaultHeadersTest extends InternalMiddlewareTestCase
{
    
    /** @test */
    public function all_headers_are_added_to_the_response()
    {
        $response = $this->runMiddleware(
            new DefaultHeaders(['foo' => 'bar', 'baz' => 'biz']),
            $this->frontendRequest()
        );
        
        $response->assertNextMiddlewareCalled();
        
        $response->assertHeader('foo', 'bar');
        $response->assertHeader('baz', 'biz');
    }
    
    /** @test */
    public function x_frame_headers_are_added_by_default()
    {
        $response = $this->runMiddleware(
            new DefaultHeaders(),
            $this->frontendRequest()
        );
        
        $response->assertNextMiddlewareCalled();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
    
    /** @test */
    public function header_values_are_not_overwritten()
    {
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $response->withHeader('foo', 'bar');
        });
        
        $response =
            $this->runMiddleware(new DefaultHeaders(['foo' => 'baz']), $this->frontendRequest());
        $response->assertNextMiddlewareCalled();
        
        $response->assertHeader('foo', 'bar');
    }
    
}