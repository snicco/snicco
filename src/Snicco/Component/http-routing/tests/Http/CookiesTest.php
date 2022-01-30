<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Http\Cookie;
use Snicco\Component\HttpRouting\Http\Cookies;

use function urlencode;

final class CookiesTest extends TestCase
{
    
    /** @test */
    public function cookies_are_immutable()
    {
        $cookies = new Cookies();
        $this->assertSame([], $cookies->toHeaders());
        
        $cookies_new = $cookies->withCookie(new Cookie('foo', 'bar'));
        
        $this->assertSame([], $cookies->toHeaders());
        $this->assertSame(
            ['foo=bar; path=/; secure; HostOnly; HttpOnly; SameSite=Lax'],
            $cookies_new->toHeaders()
        );
    }
    
    /** @test */
    public function cookies_can_be_converted_to_an_array_of_headers()
    {
        $cookie1 = (new Cookie('foo', 'val1'))->withPath('/foo')->withDomain('foo.com');
        $cookie2 = (new Cookie('bar', 'val2'))->withSameSite('strict');
        $cookie3 = (new Cookie('baz', 'münchen'))->withJsAccess();
        
        $cookies = (new Cookies())->withCookie($cookie1)
                                  ->withCookie($cookie2)
                                  ->withCookie($cookie3);
        
        $headers = $cookies->toHeaders();
        
        $this->assertCount(3, $headers);
        
        $this->assertSame(
            "foo=val1; domain=foo.com; path=/foo; secure; HostOnly; HttpOnly; SameSite=Lax",
            $headers[0]
        );
        $this->assertSame(
            "bar=val2; path=/; secure; HostOnly; HttpOnly; SameSite=Strict",
            $headers[1]
        );
        $this->assertSame(
            "baz=".urlencode('münchen')."; path=/; secure; HostOnly; SameSite=Lax",
            $headers[2]
        );
    }
    
}