<?php

declare(strict_types=1);

namespace Snicco\Middleware\TrailingSlash\Tests;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\TrailingSlash\TrailingSlash;

/**
 * @internal
 */
final class TrailingSlashTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function a_request_without_trailing_slash_is_redirected(): void
    {
        $request = $this->frontendRequest('https://foo.com/bar');

        $response = $this->runMiddleware(new TrailingSlash(true), $request);

        $response->assertNextMiddlewareNotCalled();
        $response->assertableResponse()
            ->assertRedirect();
        $response->assertableResponse()
            ->assertStatus(301);

        $response->assertableResponse()
            ->assertRedirectPath('/bar/');
    }

    /**
     * @test
     */
    public function a_request_with_trailing_slash_is_not_redirected(): void
    {
        $request = $this->frontendRequest('https://foo.com/bar/');

        $response = $this->runMiddleware(new TrailingSlash(true), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertOk();
    }

    /**
     * @test
     */
    public function a_request_with_trailing_slash_is_redirected(): void
    {
        $request = $this->frontendRequest('https://foo.com/bar/');

        $response = $this->runMiddleware(new TrailingSlash(false), $request);

        $response->assertNextMiddlewareNotCalled();
        $response->assertableResponse()
            ->assertRedirectPath('/bar')
            ->assertStatus(301);
    }

    /**
     * @test
     */
    public function a_request_without_trailing_slash_is_not_redirected(): void
    {
        $request = $this->frontendRequest('https://foo.com/bar');

        $response = $this->runMiddleware(new TrailingSlash(false), $request);

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertOk();
    }

    /**
     * @test
     */
    public function a_request_to_the_home_page_is_not_affected_if_is_has_a_trailing_slash(): void
    {
        $request = $this->frontendRequest('/');

        $response = $this->runMiddleware(new TrailingSlash(false), $request);
        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertOk();

        $response = $this->runMiddleware(new TrailingSlash(true), $request);
        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertOk();
    }

    /**
     * @test
     */
    public function a_request_to_the_home_page_is_not_affected_if_is_has_no_trailing_slash(): void
    {
        $request = $this->psrServerRequestFactory()
            ->createServerRequest('GET', 'https://foo.com', []);

        $response = $this->runMiddleware(new TrailingSlash(false), new Request($request));
        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertOk();

        $response = $this->runMiddleware(new TrailingSlash(true), new Request($request));
        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertOk();
    }
}
