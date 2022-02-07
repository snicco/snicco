<?php

declare(strict_types=1);

namespace Snicco\Component\ParameterBag\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\ParameterBag\ParameterBag;

final class ParameterBagTest extends TestCase
{

    /**
     * @test
     */
    public function testHas(): void
    {
        $bag = new ParameterBag([
            'foo' => [
                'bar' => ['baz' => 'biz'],
                'boo' => 'bam',
            ],
        ]);

        $this->assertFalse($bag->has('bar'));
        $this->assertTrue($bag->has('foo'));
        $this->assertTrue($bag->has('foo.bar'));
        $this->assertTrue($bag->has('foo.bar.baz'));
        $this->assertTrue($bag->has('foo.boo'));
        $this->assertFalse($bag->has('foo.baz'));
    }

    /**
     * @test
     */
    public function testGet(): void
    {
        $bag = new ParameterBag([
            'foo' => [
                'bar' => ['baz' => 'biz'],
                'boo' => 'bam',
            ],
        ]);

        $this->assertEquals([
            'bar' => ['baz' => 'biz'],
            'boo' => 'bam',
        ], $bag->get('foo'));

        $this->assertEquals(['baz' => 'biz'], $bag->get('foo.bar'));
        $this->assertEquals('biz', $bag->get('foo.bar.baz'));
        $this->assertEquals('bam', $bag->get('foo.boo'));
        $this->assertEquals(null, $bag->get('foo.bogus'));
        $this->assertEquals('default', $bag->get('foo.bogus', 'default'));
    }

    /**
     * @test
     */
    public function testAdd(): void
    {
        $bag = new ParameterBag([
            'foo' => [
                'bar' => ['baz' => 'biz'],
                'boo' => 'bam',
            ],
        ]);

        $bag->add(['foo.boo' => 'bang', 'bar' => 'baz']);

        $this->assertEquals('bang', $bag->get('foo.boo'));
        $this->assertEquals('baz', $bag->get('bar'));
    }

    /**
     * @test
     */
    public function testSet(): void
    {
        $bag = new ParameterBag([
            'foo' => [
                'bar' => ['baz' => 'biz'],
                'boo' => 'bam',
            ],
        ]);

        $bag->set('foo.bar.baz', 'foobar');

        $this->assertEquals('foobar', $bag->get('foo.bar.baz'));
    }

    /**
     * @test
     */
    public function testPrepend(): void
    {
        $bag = new ParameterBag([
            'users' => [
                'names' => [
                    'calvin',
                    'marlon',
                ],
            ],
        ]);

        $bag->prepend('users.names', 'jon');

        $this->assertEquals([
            'jon',
            'calvin',
            'marlon',
        ], $bag->get('users.names'));
    }

    /**
     * @test
     */
    public function testAppend(): void
    {
        $bag = new ParameterBag([
            'users' => [
                'names' => [
                    'calvin',
                    'marlon',
                ],
            ],
        ]);

        $bag->append('users.names', 'jon');

        $this->assertEquals([
            'calvin',
            'marlon',
            'jon',
        ], $bag->get('users.names'));
    }

    /**
     * @test
     */
    public function testRemove(): void
    {
        $bag = new ParameterBag([
            'foo' => [
                'bar' => ['baz' => 'biz'],
                'boo' => 'bam',
            ],
        ]);

        $bag->remove('foo.bar.baz');

        $this->assertSame([
            'bar' => [],
            'boo' => 'bam',
        ], $bag->get('foo'));
    }

    /**
     * @test
     */
    public function testToArray(): void
    {
        $bag = new ParameterBag(
            $arr = [
                'foo' => [
                    'bar' => ['baz' => 'biz'],
                    'boo' => 'bam',
                ],
            ]
        );

        $this->assertEquals($arr, $bag->toArray());
    }

    /**
     * @test
     */
    public function test_arrayAccess(): void
    {
        $bag = new ParameterBag([
            'foo' => [
                'bar' => ['baz' => 'biz'],
                'boo' => 'bam',
            ],
        ]);

        $this->assertEquals('biz', $bag['foo.bar.baz']);

        $this->assertTrue(isset($bag['foo.boo']));
        $this->assertFalse(isset($bag['foo.baz']));

        $bag['foo.bar.baz'] = 'foobar';
        $this->assertEquals('foobar', $bag['foo.bar.baz']);

        unset($bag['foo.bar.baz']);

        $this->assertEquals([], $bag->get('foo.bar'));
    }

    /**
     * @test
     */
    public function test_prepend_throws_exception_for_non_array(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('not an array');

        $bag = new ParameterBag();
        $bag->set('foo', 'bar');

        $bag->prepend('foo', 'baz');
    }

    /**
     * @test
     */
    public function test_append_throws_exception_for_non_array(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('not an array');

        $bag = new ParameterBag();
        $bag->set('foo', 'bar');

        $bag->append('foo', 'baz');
    }

}