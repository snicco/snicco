<?php

declare(strict_types=1);

namespace Snicco\Middleware\MustMatchRoute\Tests;

use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Middleware\MustMatchRoute\MustMatchRoute;

/**
 * @internal
 */
final class MustMatchRouteTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function test_exception_for_delegated_response(): void
    {
        $this->withNextMiddlewareResponse(fn (): DelegatedResponse => $this->responseFactory()->delegate());

        $middleware = new MustMatchRoute();

        try {
            $this->runMiddleware($middleware, $this->frontendRequest());
            $this->fail('Exception should have been thrown');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->statusCode());
        }
    }

    /**
     * @test
     */
    public function test_no_exception_for_handled_response(): void
    {
        $this->withNextMiddlewareResponse(fn (): Response => $this->responseFactory()->html('foo'));

        $middleware = new MustMatchRoute();

        $response = $this->runMiddleware($middleware, $this->frontendRequest());
        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertOk();
    }
}
