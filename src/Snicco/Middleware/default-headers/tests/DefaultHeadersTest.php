<?php

declare(strict_types=1);

namespace Snicco\Middleware\DefaultHeaders\Tests;

use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Middleware\DefaultHeaders\DefaultHeaders;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;

class DefaultHeadersTest extends MiddlewareTestCase
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
    public function header_values_are_not_overwritten()
    {
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $response->withHeader('foo', 'bar');
        });
        
        $response = $this->runMiddleware(
            new DefaultHeaders(['foo' => 'baz']),
            $this->frontendRequest()
        );
        $response->assertNextMiddlewareCalled();
        
        $response->psr()->assertHeader('foo', 'bar');
    }
    
}