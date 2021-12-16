<?php

declare(strict_types=1);

namespace Tests\Session\unit;

use Tests\Codeception\shared\UnitTest;
use Snicco\Session\ValueObjects\CookiePool;

final class CookiePoolTest extends UnitTest
{
    
    /** @test */
    public function testFromArray()
    {
        $pool = new CookiePool(['foo' => 'bar']);
        
        $this->assertTrue($pool->has('foo'));
        $this->assertSame('bar', $pool->get('foo'));
    }
    
    /** @test */
    public function testFromSuperGlobals()
    {
        $cookie = $_COOKIE;
        $_COOKIE['foo'] = 'bar';
        
        $pool = CookiePool::fromSuperGlobals();
        
        $this->assertTrue($pool->has('foo'));
        $this->assertSame('bar', $pool->get('foo'));
        
        $_COOKIE = $cookie;
    }
    
}