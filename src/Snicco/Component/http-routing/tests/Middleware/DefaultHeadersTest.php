<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Middleware\DefaultHeaders;
use Snicco\Component\HttpRouting\Tests\InternalMiddlewareTestCase;

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
        
        $response->psr()->assertHeader('foo', 'bar');
        $response->psr()->assertHeader('baz', 'biz');
    }
    
    /** @test */
    public function x_frame_headers_are_added_by_default()
    {
        $response = $this->runMiddleware(
            new DefaultHeaders(),
            $this->frontendRequest()
        );
        
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
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
        
        $response->psr()->assertHeader('foo', 'bar');
    }
    
}