<?php

declare(strict_types=1);

namespace Snicco\Middleware\HttpsOnly\Tests;

use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\HttpsOnly\HttpsOnly;

final class HttpsOnlyTest extends MiddlewareTestCase
{

    /**
     * @test
     */
    public function no_redirect_happens_in_a_local_environment(): void
    {
        $middleware = new HttpsOnly(true);

        $request = $this->frontendRequest('http://foobar.com');

        $response = $this->runMiddleware($middleware, $request);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }

    /**
     * @test
     */
    public function http_request_are_redirected(): void
    {
        $middleware = new HttpsOnly();

        $request = $this->frontendRequest('http://foobar.com/foo/bar');

        $response = $this->runMiddleware($middleware, $request);

        $response->assertNextMiddlewareNotCalled();
        $response->psr()->assertRedirect('https://foobar.com/foo/bar', 301);
    }

    /**
     * @test
     */
    public function https_requests_are_not_redirected(): void
    {
        $middleware = new HttpsOnly();

        $request = $this->frontendRequest('https://foobar.com/foo/bar');

        $response = $this->runMiddleware($middleware, $request);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }

}