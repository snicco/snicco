<?php

declare(strict_types=1);

namespace Snicco\Component\Core;

use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Component\Core\Exception\FrozenServiceException;

trait DIContainerContractTest
{
    
    private DIContainer $container;
    
    abstract function createContainer() :DIContainer;
    
    /** @test */
    final public function testFactoryReturnsDifferentObjects()
    {
        $container = $this->createContainer();
        
        $container->factory(Foo::class, function () {
            return new Foo();
        });
        
        $foo1 = $container[Foo::class];
        $foo2 = $container->get(Foo::class);
        
        PHPUnit::assertInstanceOf(Foo::class, $foo1);
        PHPUnit::assertInstanceOf(Foo::class, $foo2);
        PHPUnit::assertNotSame($foo1, $foo2);
    }
    
    /** @test */
    final public function testSingletonReturnsSameObject()
    {
        $container = $this->createContainer();
        
        $container->singleton(Foo::class, function () {
            return new Foo();
        });
        
        $foo1 = $container[Foo::class];
        $foo2 = $container->get(Foo::class);
        
        PHPUnit::assertInstanceOf(Foo::class, $foo1);
        PHPUnit::assertInstanceOf(Foo::class, $foo2);
        PHPUnit::assertSame($foo1, $foo2);
    }
    
    /** @test */
    final public function testInstanceReturnsSameObject()
    {
        $container = $this->createContainer();
        
        $foo = new Foo();
        $container->instance(Foo::class, $foo);
        
        $foo1 = $container[Foo::class];
        $foo2 = $container->get(Foo::class);
        
        PHPUnit::assertInstanceOf(Foo::class, $foo1);
        PHPUnit::assertInstanceOf(Foo::class, $foo2);
        PHPUnit::assertSame($foo1, $foo);
        PHPUnit::assertSame($foo2, $foo);
    }
    
    /** @test */
    final public function testRedefiningWorks()
    {
        $container = $this->createContainer();
        
        $foo = new Foo();
        $container->instance('key', $foo);
        
        $bar = new Bar();
        $container->instance('key', $bar);
        
        $val = $container['key'];
        PHPUnit::assertSame($bar, $val);
    }
    
    /** @test */
    final public function testPrimitive()
    {
        $container = $this->createContainer();
        
        $container->primitive('foo', 'bar');
        
        PHPUnit::assertSame('bar', $container['foo']);
    }
    
    /** @test */
    final public function testOverwrittenFrozenServiceThrowsException()
    {
        $container = $this->createContainer();
        
        $container->factory(Foo::class, function () {
            return new Foo();
        });
        $container->singleton(Bar::class, function () {
            return new Bar();
        });
        $container->instance('foo.instance', new Foo());
        
        $foo = $container[Foo::class];
        PHPUnit::assertInstanceOf(Foo::class, $foo);
        
        $container->instance(Foo::class, $_foo = new Foo());
        $new_foo = $container[Foo::class];
        PHPUnit::assertInstanceOf(Foo::class, $foo);
        PHPUnit::assertSame($new_foo, $_foo);
        PHPUnit::assertNotSame($foo, $new_foo);
        
        // Can still be overwritten because it's not resolved yet.
        $container->singleton(Bar::class, function () {
            return new Bar();
        });
        
        $bar = $container[Bar::class];
        PHPUnit::assertInstanceOf(Bar::class, $bar);
        
        try {
            $container->factory(Bar::class, function () {
                return new Bar();
            });
            PHPUnit::fail('No exception thrown');
        } catch (FrozenServiceException $e) {
            //
        }
        
        try {
            $container->singleton(Bar::class, function () {
                return new Bar();
            });
            PHPUnit::fail('No exception thrown');
        } catch (FrozenServiceException $e) {
            //
        }
        
        $foo_as_instance = $container['foo.instance'];
        PHPUnit::assertInstanceOf(Foo::class, $foo_as_instance);
        
        try {
            $container->factory('foo.instance', function () {
                return new Foo();
            });
            PHPUnit::fail('No exception thrown');
        } catch (FrozenServiceException $e) {
            //
        }
    }
    
    /** @test */
    final public function test_overwritten_primitive_throws_exception()
    {
        $container = $this->createContainer();
        
        $container->primitive('baz', 'biz');
        
        PHPUnit::assertEquals('biz', $container['baz']);
        
        try {
            $container->primitive('baz', 'boo');
            PHPUnit::fail("Overwriting a primitive value should throw an exception.");
        } catch (FrozenServiceException $e) {
        }
    }
    
}

class Foo
{

}

class Bar
{

}