<?php

declare(strict_types=1);

namespace Snicco\Middleware\Redirect\Tests;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\Redirect\Redirect;

/**
 * @internal
 */
final class RedirectTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function test_redirect_for_configured_urls(): void
    {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo' => '/bar',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo'));

        $response->assertNextMiddlewareNotCalled();
        $response->assertableResponse()
            ->assertRedirect('/bar')
            ->assertStatus(301);
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
        $response->assertableResponse()
            ->assertRedirect('https://foobar.com/foo')
            ->assertStatus(301);
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
        $response->assertableResponse()
            ->assertOk();
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
        $response->assertableResponse()
            ->assertRedirect('/bar', 301);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/baz'));
        $response->assertableResponse()
            ->assertRedirect('/biz', 302);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/boo'));
        $response->assertableResponse()
            ->assertRedirect('/bam/', 307);
    }

    /**
     * @test
     */
    public function if_a_redirect_is_defined_with_a_query_string_the_redirect_will_only_happen_for_that_query_string(
    ): void {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo?page=60' => '/bar',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo?page=60'));
        $response->assertableResponse()
            ->assertRedirect('/bar')
            ->assertStatus(301);
        $response->assertNextMiddlewareNotCalled();

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo?page=50'));
        $response->assertableResponse()
            ->assertOk();
        $response->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function redirect_definitions_without_query_strings_will_match_all_requests_for_that_patch_no_matter_the_query_string(
        ): void {
        $middleware = $this->getMiddleware([
            301 => [
                '/foo/bar' => '/baz',
            ],
        ]);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo/bar?page=60'));
        $response->assertableResponse()
            ->assertRedirect('/baz')
            ->assertStatus(301);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/foo/bar?baz=biz'));
        $response->assertableResponse()
            ->assertRedirect('/baz')
            ->assertStatus(301);
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
        $response->assertableResponse()
            ->assertRedirect('/bar', 301);

        $response = $this->runMiddleware($middleware, $this->frontendRequest('/baz'));
        $response->assertableResponse()
            ->assertRedirect('/bar', 301);
    }

    /**
     * @test
     */
    public function exceptions_are_thrown_for_bad_status_codes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$status');
        $this->getMiddleware([
            400 => [
                '/foo' => '/bar',
                '/baz' => '/bar',
            ],
        ]);
    }

    /**
     * @param array<positive-int,array<string,string>> $redirects
     */
    private function getMiddleware(array $redirects = []): Redirect
    {
        return new Redirect($redirects);
    }
}
