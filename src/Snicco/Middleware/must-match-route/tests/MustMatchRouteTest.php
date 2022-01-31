<?php

declare(strict_types=1);

namespace Snicco\Middleware\MustMatchRoute\Tests;

use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Middleware\MustMatchRoute\MustMatchRoute;

final class MustMatchRouteTest extends MiddlewareTestCase
{

    /** @test */
    public function test_exception_for_delegated_response()
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->delegate();
        });

        $middleware = new MustMatchRoute();

        try {
            $response = $this->runMiddleware($middleware, $this->frontendRequest());
            $this->fail('Exception should have been thrown');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->statusCode());
        }
    }

    /** @test */
    public function test_no_exception_for_handled_response()
    {
        $this->withNextMiddlewareResponse(function () {
            return $this->getResponseFactory()->html('foo');
        });

        $middleware = new MustMatchRoute();

        $response = $this->runMiddleware($middleware, $this->frontendRequest());
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }

}