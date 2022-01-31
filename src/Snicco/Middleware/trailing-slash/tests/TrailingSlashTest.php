<?php

declare(strict_types=1);

namespace Snicco\Middleware\TrailingSlash\Tests;

use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\TrailingSlash\TrailingSlash;

class TrailingSlashTest extends MiddlewareTestCase
{

    /** @test */
    public function a_request_without_trailing_slash_is_redirected()
    {
        $request = $this->frontendRequest('https://foo.com/bar');

        $response = $this->runMiddleware(new TrailingSlash(true), $request);

        $response->assertNextMiddlewareNotCalled();
        $response->psr()->assertRedirect();
        $response->psr()->assertStatus(301);

        $response->psr()->assertRedirectPath('/bar/');
    }

    /** @test */
    public function a_request_with_trailing_slash_is_not_redirected()
    {
        $request = $this->frontendRequest('https://foo.com/bar/');

        $response = $this->runMiddleware(new TrailingSlash(true), $request);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }

    /** @test */
    public function a_request_with_trailing_slash_is_redirected()
    {
        $request = $this->frontendRequest('https://foo.com/bar/');

        $response = $this->runMiddleware(new TrailingSlash(false), $request);

        $response->assertNextMiddlewareNotCalled();
        $response->psr()->assertRedirectPath('/bar')->assertStatus(301);
    }

    /** @test */
    public function a_request_without_trailing_slash_is_not_redirected()
    {
        $request = $this->frontendRequest('https://foo.com/bar');

        $response = $this->runMiddleware(new TrailingSlash(false), $request);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }

}
