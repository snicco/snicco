<?php

declare(strict_types=1);

namespace Snicco\Middleware\Redirect\Tests;

use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\Redirect\Redirect;

class RedirectTest extends MiddlewareTestCase
{

    /**
     * @test
     */
    public function testRedirectForConfiguredUrls(): void
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareNotCalled();
        $response->psr()->assertRedirect('/bar')->assertStatus(301);
    }

    /**
     * @test
     */
    public function redirects_can_go_to_external_urls(): void
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo/' => 'https://foobar.com/foo',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo/'));
        $response->psr()->assertRedirect('https://foobar.com/foo')->assertStatus(301);
    }

    /**
     * @test
     */
    public function other_requests_are_not_redirected(): void
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/bogus'));

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }

    /**
     * @test
     */
    public function test_redirects_can_have_a_custom_status_code(): void
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
            ],
            302 => [
                '/baz' => '/biz',
                '/a/' => '/b/',
            ],
            307 => [
                '/boo' => '/bam/',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        $response->psr()->assertRedirect('/bar', 301);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/baz'));
        $response->psr()->assertRedirect('/biz', 302);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/boo'));
        $response->psr()->assertRedirect('/bam/', 307);
    }

    /**
     * @test
     */
    public function if_a_redirect_is_defined_with_a_query_string_the_redirect_will_only_happen_for_that_query_string(): void
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo?page=60' => '/bar',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo?page=60'));
        $response->psr()->assertRedirect('/bar')->assertStatus(301);
        $response->assertNextMiddlewareNotCalled();

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo?page=50'));
        $response->psr()->assertOk();
        $response->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function redirect_definitions_without_query_strings_will_match_all_requests_for_that_patch_no_matter_the_query_string(
    ): void
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo/bar' => '/baz',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo/bar?page=60'));
        $response->psr()->assertRedirect('/baz')->assertStatus(301);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo/bar?baz=biz'));
        $response->psr()->assertRedirect('/baz')->assertStatus(301);
    }

    /**
     * @test
     */
    public function two_urls_can_be_redirected_to_the_same_location(): void
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
                '/baz' => '/bar',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
        $response->psr()->assertRedirect('/bar', 301);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/baz'));
        $response->psr()->assertRedirect('/bar', 301);
    }

    private function getMiddleware(array $redirects = []): Redirect
    {
        return new Redirect($redirects);
    }

}