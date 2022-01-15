<?php

declare(strict_types=1);

namespace Tests\HttpRouting\unit\Http;

use DateTime;
use LogicException;
use InvalidArgumentException;
use Snicco\HttpRouting\Http\Cookie;
use Tests\Codeception\shared\UnitTest;
use Snicco\Component\Core\Utils\Carbon;

class CookieTest extends UnitTest
{
    
    /** @test */
    public function testIsImmutable()
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie2 = $cookie->withPath('/web');
        
        $this->assertNotSame($cookie, $cookie2);
    }
    
    public function testDefault()
    {
        $cookie = new Cookie('foo', 'bar');
        
        $this->assertSame([
            'value' => 'bar',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties());
    }
    
    public function testAllowJs()
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie = $cookie->withJsAccess();
        
        $this->assertSame([
            'value' => 'bar',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => false,
            'samesite' => 'Lax',
        ], $cookie->properties());
    }
    
    public function testAllowUnsecure()
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie = $cookie->withUnsecureHttp();
        
        $this->assertSame([
            'value' => 'bar',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties());
    }
    
    public function testSameSite()
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie = $cookie->withSameSite('strict');
        $this->assertSame([
            'value' => 'bar',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ], $cookie->properties());
        
        $cookie = $cookie->withSameSite('lax');
        $this->assertSame([
            'value' => 'bar',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties());
        
        $cookie = $cookie->withSameSite('none');
        $this->assertSame([
            'value' => 'bar',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None',
        ], $cookie->properties());
        
        $this->expectException(LogicException::class);
        
        $cookie->withSameSite('bogus');
    }
    
    public function testExpiresInteger()
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie = $cookie->withExpiryTimestamp(1000);
        $this->assertSame([
            'value' => 'bar',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => 1000,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties());
    }
    
    public function testExpiresDatetimeInterface()
    {
        $cookie = new Cookie('foo', 'bar');
        
        $date = new DateTime('2000-01-01');
        
        $cookie = $cookie->withExpiryTimestamp($date);
        
        $this->assertSame([
            'value' => 'bar',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => $date->getTimestamp(),
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ], $cookie->properties());
    }
    
    public function testExpiresInvalidArgumentThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $cookie = new Cookie('foo', 'bar');
        $cookie->withExpiryTimestamp('1000');
    }
    
    public function testValueIsUrlEncoded()
    {
        $cookie = new Cookie('foo_cookie', 'foo bar');
        
        $this->assertSame(urlencode('foo bar'), $cookie->properties()['value']);
        
        $cookie = new Cookie('foo_cookie', 'foo bar', false);
        
        $this->assertSame('foo bar', $cookie->properties()['value']);
    }
    
}
