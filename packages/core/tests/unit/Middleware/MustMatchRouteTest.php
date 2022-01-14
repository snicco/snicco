<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use Tests\Core\InternalMiddlewareTestCase;
use Snicco\Core\Middleware\MustMatchRoute;
use Snicco\Core\ExceptionHandling\Exceptions\NotFoundException;

final class MustMatchRouteTest extends InternalMiddlewareTestCase
{
    
    /** @test */
    public function test_exception_for_delegated_response()
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->delegate();
        });
        
        $middleware = new MustMatchRoute();
        
        $this->expectException(NotFoundException::class);
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest());
    }
    
    /** @test */
    public function test_no_exception_for_handled_response()
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->html('foo');
        });
        
        $middleware = new MustMatchRoute();
        
        $response = $this->runMiddleware($middleware, $this->frontendRequest());
        $response->assertOk()->assertNextMiddlewareCalled();
    }
    
}