<?php

declare(strict_types=1);

namespace Snicco\Middleware\ShareCookies\Tests;

use Snicco\Component\HttpRouting\Http\Cookie;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Middleware\ShareCookies\ShareCookies;

class ShareCookiesTest extends MiddlewareTestCase
{

    /**
     * @test
     */
    public function response_cookies_can_be_added(): void
    {
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $response->withCookie(new Cookie('foo', 'bar'));
        });

        $response = $this->runMiddleware(
            new ShareCookies(),
            $this->frontendRequest()
        );

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertHeader(
            'Set-Cookie',
            'foo=bar; path=/; SameSite=Lax; secure; HostOnly; HttpOnly'
        );
    }

    /**
     * @test
     */
    public function multiple_cookies_can_be_added(): void
    {
        $this->withNextMiddlewareResponse(function (Response $response) {
            $cookie1 = new Cookie('foo', 'bar');
            $cookie2 = new Cookie('baz', 'biz');

            return $response->withCookie($cookie1)
                ->withCookie($cookie2);
        });

        $response = $this->runMiddleware(
            new ShareCookies(),
            $this->frontendRequest()
        );

        $cookie_header = $response->psr()->getHeader('Set-Cookie');

        $this->assertSame(
            'foo=bar; path=/; SameSite=Lax; secure; HostOnly; HttpOnly',
            $cookie_header[0]
        );
        $this->assertSame(
            'baz=biz; path=/; SameSite=Lax; secure; HostOnly; HttpOnly',
            $cookie_header[1]
        );
    }

    /**
     * @test
     */
    public function a_cookie_can_be_deleted(): void
    {
        $this->withNextMiddlewareResponse(function (Response $response) {
            return $response->withoutCookie('foo');
        });

        $response = $this->runMiddleware(
            new ShareCookies(),
            $this->frontendRequest()
        );

        $cookie_header = $response->psr()->getHeader('Set-Cookie');
        $this->assertSame(
            'foo=deleted; path=/; expires=Thu, 01-Jan-1970 00:00:01 UTC; SameSite=Lax; secure; HostOnly; HttpOnly',
            $cookie_header[0]
        );
    }

    /**
     * @test
     */
    public function everything_works_if_the_response_has_no_cookies_added(): void
    {
        $response = $this->runMiddleware(
            new ShareCookies(),
            $this->frontendRequest()
        );

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertHeaderMissing('set-cookie');
    }

}



