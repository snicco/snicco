<?php

declare(strict_types=1);

namespace Snicco\Middleware\MethodOverride\Tests;

use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\MethodOverride\MethodOverride;

class MethodOverrideTest extends MiddlewareTestCase
{

    private MethodOverride $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new MethodOverride();
    }

    /**
     * @test
     */
    public function the_method_can_be_overwritten_for_post_requests(): void
    {
        $request = $this->frontendRequest('/', [], 'POST')->withParsedBody([
            '_method' => 'PUT',
        ]);

        $response = $this->runMiddleware($this->middleware, $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame('PUT', $this->getReceivedRequest()->getMethod());
    }

    /**
     * @test
     */
    public function the_method_cant_be_overwritten_for_anything_but_post_requests(): void
    {
        $request = $this->frontendRequest('/foo')->withParsedBody([
            '_method' => 'PUT',
        ]);

        $response = $this->runMiddleware($this->middleware, $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame('GET', $this->getReceivedRequest()->getMethod());
    }

    /**
     * @test
     */
    public function the_method_can_be_overwritten_with_the_method_override_header(): void
    {
        $request = $this->frontendRequest('/foo', [], 'POST')
            ->withHeader('X-HTTP-Method-Override', 'PUT');

        $response = $this->runMiddleware($this->middleware, $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame('PUT', $this->getReceivedRequest()->getMethod());

        $request = $this->frontendRequest('/foo')
            ->withHeader('X-HTTP-Method-Override', 'PUT');

        $response = $this->runMiddleware($this->middleware, $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame('GET', $this->getReceivedRequest()->getMethod());
    }

}