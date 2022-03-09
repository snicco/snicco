<?php

declare(strict_types=1);

namespace Snicco\Middleware\MethodOverride\Tests;

use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\MethodOverride\MethodOverride;

/**
 * @internal
 */
final class MethodOverrideTest extends MiddlewareTestCase
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
        $this->assertSame('PUT', $this->receivedRequest()->getMethod());
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
        $this->assertSame('GET', $this->receivedRequest()->getMethod());
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
        $this->assertSame('PUT', $this->receivedRequest()->getMethod());

        $request = $this->frontendRequest('/foo')
            ->withHeader('X-HTTP-Method-Override', 'PUT');

        $response = $this->runMiddleware($this->middleware, $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame('GET', $this->receivedRequest()->getMethod());
    }

    /**
     * @test
     */
    public function a_post_request_without_any_overrite_stays_untouched(): void
    {
        $request = $this->frontendRequest('/', [], 'POST')->withParsedBody([
            'foo' => 'bar',
        ]);

        $response = $this->runMiddleware($this->middleware, $request);

        $response->assertNextMiddlewareCalled();
        $this->assertSame('POST', $this->receivedRequest()->getMethod());
    }

    /**
     * @test
     */
    public function a_post_request_can_only_be_changed_to_put_patch_and_delete(): void
    {
        $request = $this->frontendRequest('/', [], 'POST')->withParsedBody([
            '_method' => 'PUT',
        ]);

        $response = $this->runMiddleware($this->middleware, $request);
        $response->assertNextMiddlewareCalled();
        $this->assertSame('PUT', $this->receivedRequest()->getMethod());

        $request = $this->frontendRequest('/', [], 'POST')->withParsedBody([
            '_method' => 'PATCH',
        ]);

        $response = $this->runMiddleware($this->middleware, $request);
        $response->assertNextMiddlewareCalled();
        $this->assertSame('PATCH', $this->receivedRequest()->getMethod());

        $request = $this->frontendRequest('/', [], 'POST')->withParsedBody([
            '_method' => 'DELETE',
        ]);

        $response = $this->runMiddleware($this->middleware, $request);
        $response->assertNextMiddlewareCalled();
        $this->assertSame('DELETE', $this->receivedRequest()->getMethod());

        $request = $this->frontendRequest('/', [], 'POST')->withParsedBody([
            '_method' => 'GET',
        ]);

        $response = $this->runMiddleware($this->middleware, $request);
        $response->assertNextMiddlewareCalled();
        $this->assertSame('POST', $this->receivedRequest()->getMethod());
    }
}
