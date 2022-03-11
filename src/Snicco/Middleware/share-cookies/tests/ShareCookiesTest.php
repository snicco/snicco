<?php

declare(strict_types=1);

namespace Snicco\Middleware\ShareCookies\Tests;

use Snicco\Component\HttpRouting\Http\Cookie;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\ShareCookies\ShareCookies;

/**
 * @internal
 */
final class ShareCookiesTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function response_cookies_can_be_added(): void
    {
        $this->withNextMiddlewareResponse(fn (Response $response) => $response->withCookie(new Cookie('foo', 'bar')));

        $response = $this->runMiddleware(new ShareCookies(), $this->frontendRequest());

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertHeader('Set-Cookie', 'foo=bar; Path=/; SameSite=Lax; Secure; HostOnly; HttpOnly');
    }

    /**
     * @test
     */
    public function multiple_cookies_can_be_added(): void
    {
        $this->withNextMiddlewareResponse(function (Response $response): Response {
            $cookie1 = new Cookie('foo', 'bar');
            $cookie2 = new Cookie('baz', 'biz');

            return $response->withCookie($cookie1)
                ->withCookie($cookie2);
        });

        $response = $this->runMiddleware(new ShareCookies(), $this->frontendRequest());

        $cookie_header = $response->assertableResponse()
            ->getHeader('Set-Cookie');

        $this->assertSame('foo=bar; Path=/; SameSite=Lax; Secure; HostOnly; HttpOnly', $cookie_header[0]);
        $this->assertSame('baz=biz; Path=/; SameSite=Lax; Secure; HostOnly; HttpOnly', $cookie_header[1]);
    }

    /**
     * @test
     */
    public function a_cookie_can_be_deleted(): void
    {
        $this->withNextMiddlewareResponse(fn (Response $response) => $response->withoutCookie('foo'));

        $response = $this->runMiddleware(new ShareCookies(), $this->frontendRequest());

        $cookie_header = $response->assertableResponse()
            ->getHeader('Set-Cookie');
        $this->assertSame(
            'foo=deleted; Path=/; Expires=Thu, 01-Jan-1970 00:00:01 GMT; SameSite=Lax; Secure; HostOnly; HttpOnly',
            $cookie_header[0]
        );
    }

    /**
     * @test
     */
    public function everything_works_if_the_response_has_no_cookies_added(): void
    {
        $response = $this->runMiddleware(new ShareCookies(), $this->frontendRequest());

        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertHeaderMissing('set-cookie');
    }
}
