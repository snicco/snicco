<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use DateTime;
use LogicException;
use Snicco\Http\Cookie;
use Snicco\Support\Carbon;
use InvalidArgumentException;
use Tests\Codeception\shared\UnitTest;

class CookieTest extends UnitTest
{
    
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
    
    public function testSetProperties()
    {
        $cookie = new Cookie('foo', 'bar');
        
        $cookie->setProperties([
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Strict',
        ]);
        
        $this->assertSame([
            'value' => 'bar',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Strict',
        ], $cookie->properties());
    }
    
    public function testAllowJs()
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie->allowJs();
        
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
        $cookie->allowUnsecure();
        
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
        $cookie->sameSite('strict');
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
        
        $cookie->sameSite('lax');
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
        
        $cookie->sameSite('none');
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
        
        $cookie->sameSite('bogus');
    }
    
    public function testExpiresInteger()
    {
        $cookie = new Cookie('foo', 'bar');
        $cookie->expires(1000);
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
        
        $cookie->expires($date);
        
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
    
    public function testExpiresCarbon()
    {
        $cookie = new Cookie('foo', 'bar');
        
        $date = Carbon::createFromDate('2000', '01', '01');
        
        $cookie->expires($date);
        
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
        $cookie->expires('1000');
    }
    
    public function testValueIsUrlEncoded()
    {
        $cookie = new Cookie('foo_cookie', 'foo bar');
        
        $this->assertSame(urlencode('foo bar'), $cookie->properties()['value']);
        
        $cookie = new Cookie('foo_cookie', 'foo bar', false);
        
        $this->assertSame('foo bar', $cookie->properties()['value']);
    }
    
}
