<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Http\Cookie;

class CookieTest extends TestCase
{

    /**
     * @test
     */
    public function testIsImmutable(): void
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie2 = $cookie->withPath('/web');

        $this->assertNotSame($cookie, $cookie2);
        $this->assertSame('foo', $cookie->name);
        $this->assertSame('bar', $cookie->value);
    }

    public function testDefault(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $this->assertSame([
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties);

        $this->assertSame('bar', $cookie->value);
        $this->assertSame('foo', $cookie->name);
    }

    public function testAllowJs(): void
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie = $cookie->withJsAccess();

        $this->assertSame([
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => false,
            'samesite' => 'Lax',
        ], $cookie->properties);
    }

    public function testAllowUnsecure(): void
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie = $cookie->withUnsecureHttp();

        $this->assertSame([
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties);
    }

    public function testSameSite(): void
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie = $cookie->withSameSite('strict');
        $this->assertSame([
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ], $cookie->properties);

        $cookie = $cookie->withSameSite('lax');
        $this->assertSame([
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties);

        $cookie = $cookie->withSameSite('none');
        $this->assertSame([
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None',
        ], $cookie->properties);

        $this->expectException(LogicException::class);

        $cookie->withSameSite('bogus');
    }

    public function testExpiresInteger(): void
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie = $cookie->withExpiryTimestamp(1000);
        $this->assertSame([
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => 1000,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties);
    }

    public function testExpiresDatetimeInterface(): void
    {
        $cookie = new Cookie('foo', 'bar');

        $date = new DateTimeImmutable('2000-01-01');

        $cookie = $cookie->withExpiryTimestamp($date);

        $this->assertSame([
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => $date->getTimestamp(),
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties);
    }

    public function testExpiresInvalidArgumentThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cookie = new Cookie('foo', 'bar');
        $cookie->withExpiryTimestamp('1000');
    }

    public function testValueIsNotUrlEncoded(): void
    {
        $cookie = new Cookie('foo_cookie', 'foo bar');

        $this->assertSame('foo bar', $cookie->value);
    }

}
