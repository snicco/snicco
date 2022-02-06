<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\CookiePool;

final class CookiePoolTest extends TestCase
{

    /**
     * @test
     */
    public function testFromArray(): void
    {
        $pool = new CookiePool(['foo' => 'bar']);

        $this->assertTrue($pool->has('foo'));
        $this->assertSame('bar', $pool->get('foo'));
    }

    /**
     * @test
     */
    public function testFromSuperGlobals(): void
    {
        $cookie = $_COOKIE;
        $_COOKIE['foo'] = 'bar';

        $pool = CookiePool::fromSuperGlobals();

        $this->assertTrue($pool->has('foo'));
        $this->assertSame('bar', $pool->get('foo'));

        $_COOKIE = $cookie;
    }

}