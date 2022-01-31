<?php

declare(strict_types=1);

namespace Snicco\Component\ParameterBag\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\ParameterBag\ParameterPag;

final class ParameterBagTest extends TestCase
{
    
    /** @test */
    public function testHas()
    {
        $bag = new ParameterPag([
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
    
    /** @test */
    public function testGet()
    {
        $bag = new ParameterPag([
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
    
    /** @test */
    public function testAdd()
    {
        $bag = new ParameterPag([
            'foo' => [
                'bar' => ['baz' => 'biz'],
                'boo' => 'bam',
            ],
        ]);
        
        $bag->add(['foo.boo' => 'bang', 'bar' => 'baz']);
        
        $this->assertEquals('bang', $bag->get('foo.boo'));
        $this->assertEquals('baz', $bag->get('bar'));
    }
    
    /** @test */
    public function testSet()
    {
        $bag = new ParameterPag([
            'foo' => [
                'bar' => ['baz' => 'biz'],
                'boo' => 'bam',
            ],
        ]);
        
        $bag->set('foo.bar.baz', 'foobar');
        
        $this->assertEquals('foobar', $bag->get('foo.bar.baz'));
    }
    
    /** @test */
    public function testPrepend()
    {
        $bag = new ParameterPag([
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
    
    /** @test */
    public function testAppend()
    {
        $bag = new ParameterPag([
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
    
    /** @test */
    public function testRemove()
    {
        $bag = new ParameterPag([
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
    
    /** @test */
    public function testToArray()
    {
        $bag = new ParameterPag(
            $arr = [
                'foo' => [
                    'bar' => ['baz' => 'biz'],
                    'boo' => 'bam',
                ],
            ]
        );
        
        $this->assertEquals($arr, $bag->toArray());
    }
    
    /** @test */
    public function test_arrayAccess()
    {
        $bag = new ParameterPag([
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
    
}