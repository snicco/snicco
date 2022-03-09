<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Testing;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Testing\AssertableCookie;

final class AssertableCookieTest extends TestCase
{
    /**
     * @test
     */
    public function test_cookie_header_is_parsed_correctly(): void
    {
        $cookie = new AssertableCookie(
            'foo=bar; Path=/; Expires=Thu, 01-Jan-2024 00:00:01 GMT; SameSite=Lax; Secure; HostOnly; HttpOnly'
        );

        $this->assertSame('bar', $cookie->value);
        $this->assertSame('/', $cookie->path);
        $this->assertSame('Thu, 01-Jan-2024 00:00:01 GMT', $cookie->expires);
        $this->assertSame('Lax', $cookie->same_site);
        $this->assertSame(true, $cookie->secure);
        $this->assertSame(true, $cookie->http_only);
    }
}
