<?php

declare(strict_types=1);

namespace Tests\PimpleContainer\unit;

use Pimple\Container;
use Tests\Codeception\shared\UnitTest;
use Snicco\Shared\FrozenServiceException;
use Snicco\PimpleContainer\PimpleContainerAdapter;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Codeception\shared\TestDependencies\Bar;

final class PimpleContainerAdapterTest extends UnitTest
{
    
    /**
     * @var PimpleContainerAdapter
     */
    private $pimple_adapter;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->pimple_adapter = new PimpleContainerAdapter(new Container());
    }
    
    /** @test */
    public function testFactoryReturnsDifferentObjects()
    {
        $this->pimple_adapter->factory(Foo::class, function () {
            return new Foo();
        });
        
        $foo1 = $this->pimple_adapter[Foo::class];
        $foo2 = $this->pimple_adapter->get(Foo::class);
        
        $this->assertInstanceOf(Foo::class, $foo1);
        $this->assertInstanceOf(Foo::class, $foo2);
        $this->assertNotSame($foo1, $foo2);
    }
    
    /** @test */
    public function testSingletonReturnsSameObject()
    {
        $this->pimple_adapter->singleton(Foo::class, function () {
            return new Foo();
        });
        
        $foo1 = $this->pimple_adapter[Foo::class];
        $foo2 = $this->pimple_adapter->get(Foo::class);
        
        $this->assertInstanceOf(Foo::class, $foo1);
        $this->assertInstanceOf(Foo::class, $foo2);
        $this->assertSame($foo1, $foo2);
    }
    
    /** @test */
    public function testInstanceReturnsSameObject()
    {
        $foo = new Foo();
        $this->pimple_adapter->instance(Foo::class, $foo);
        
        $foo1 = $this->pimple_adapter[Foo::class];
        $foo2 = $this->pimple_adapter->get(Foo::class);
        
        $this->assertInstanceOf(Foo::class, $foo1);
        $this->assertInstanceOf(Foo::class, $foo2);
        $this->assertSame($foo1, $foo);
        $this->assertSame($foo2, $foo);
    }
    
    /** @test */
    public function testRedefiningWorks()
    {
        $foo = new Foo();
        $this->pimple_adapter->instance('key', $foo);
        
        $bar = new Bar();
        $this->pimple_adapter->instance('key', $bar);
        
        $val = $this->pimple_adapter['key'];
        $this->assertSame($bar, $val);
    }
    
    /** @test */
    public function testPrimitive()
    {
        $this->pimple_adapter->primitive('foo', 'bar');
        
        $this->assertSame('bar', $this->pimple_adapter['foo']);
        
        $this->pimple_adapter->primitive('foo', 'baz');
    }
    
    /** @test */
    public function testOverwrittenFrozenServiceThrowsException()
    {
        $this->pimple_adapter->factory(Foo::class, function () {
            return new Foo();
        });
        $this->pimple_adapter->singleton(Bar::class, function () {
            return new Bar();
        });
        $this->pimple_adapter->primitive('baz', 'biz');
        $this->pimple_adapter->instance('foo.instance', new Foo());
        
        $foo = $this->pimple_adapter[Foo::class];
        $this->assertInstanceOf(Foo::class, $foo);
        
        $this->pimple_adapter->instance(Foo::class, $_foo = new Foo());
        $new_foo = $this->pimple_adapter[Foo::class];
        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertSame($new_foo, $_foo);
        $this->assertNotSame($foo, $new_foo);
        
        // Can still be overwritten because it's not resolved yet.
        $this->pimple_adapter->singleton(Bar::class, function () {
            return new Bar();
        });
        
        $bar = $this->pimple_adapter[Bar::class];
        $this->assertInstanceOf(Bar::class, $bar);
        
        try {
            $this->pimple_adapter->factory(Bar::class, function () {
                return new Bar();
            });
            $this->fail("No exception thrown");
        } catch (FrozenServiceException $e) {
            //
        }
        
        try {
            $this->pimple_adapter->singleton(Bar::class, function () {
                return new Bar();
            });
            $this->fail("No exception thrown");
        } catch (FrozenServiceException $e) {
            //
        }
        
        $biz = $this->pimple_adapter['baz'];
        $this->assertSame('biz', $biz);
        
        $this->pimple_adapter->primitive('baz', 'boom');
        
        $boom = $this->pimple_adapter['baz'];
        $this->assertSame('boom', $boom);
        
        $foo_as_instance = $this->pimple_adapter['foo.instance'];
        $this->assertInstanceOf(Foo::class, $foo_as_instance);
        
        try {
            $this->pimple_adapter->factory('foo.instance', function () {
                return new Foo();
            });
            $this->fail('No exception thrown');
        } catch (FrozenServiceException $e) {
            //
        }
    }
    
}