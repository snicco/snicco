<?php

declare(strict_types=1);

namespace Tests\IllumianteContainerBridge\unit;

use Illuminate\Container\Container;
use Tests\Codeception\shared\UnitTest;
use Snicco\Illuminate\IlluminateDIContainer;
use Snicco\Core\Exception\FrozenServiceException;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Codeception\shared\TestDependencies\Bar;

final class IlluminateContainerAdapterTest extends UnitTest
{
    
    /**
     * @var IlluminateDIContainer
     */
    private $illuminate_container_adapter;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->illuminate_container_adapter = new IlluminateDIContainer(new Container());
    }
    
    /** @test */
    public function testFactoryReturnsDifferentObjects()
    {
        $this->illuminate_container_adapter->factory(Foo::class, function () {
            return new Foo();
        });
        
        $foo1 = $this->illuminate_container_adapter[Foo::class];
        $foo2 = $this->illuminate_container_adapter->get(Foo::class);
        
        $this->assertInstanceOf(Foo::class, $foo1);
        $this->assertInstanceOf(Foo::class, $foo2);
        $this->assertNotSame($foo1, $foo2);
    }
    
    /** @test */
    public function testSingletonReturnsSameObject()
    {
        $this->illuminate_container_adapter->singleton(Foo::class, function () {
            return new Foo();
        });
        
        $foo1 = $this->illuminate_container_adapter[Foo::class];
        $foo2 = $this->illuminate_container_adapter->get(Foo::class);
        
        $this->assertInstanceOf(Foo::class, $foo1);
        $this->assertInstanceOf(Foo::class, $foo2);
        $this->assertSame($foo1, $foo2);
    }
    
    /** @test */
    public function testInstanceReturnsSameObject()
    {
        $foo = new Foo();
        $this->illuminate_container_adapter->instance(Foo::class, $foo);
        
        $foo1 = $this->illuminate_container_adapter[Foo::class];
        $foo2 = $this->illuminate_container_adapter->get(Foo::class);
        
        $this->assertInstanceOf(Foo::class, $foo1);
        $this->assertInstanceOf(Foo::class, $foo2);
        $this->assertSame($foo1, $foo);
        $this->assertSame($foo2, $foo);
    }
    
    /** @test */
    public function testRedefiningWorks()
    {
        $foo = new Foo();
        $this->illuminate_container_adapter->instance('key', $foo);
        
        $bar = new Bar();
        $this->illuminate_container_adapter->instance('key', $bar);
        
        $val = $this->illuminate_container_adapter['key'];
        $this->assertSame($bar, $val);
    }
    
    /** @test */
    public function testPrimitive()
    {
        $this->illuminate_container_adapter->primitive('foo', 'bar');
        
        $this->assertSame('bar', $this->illuminate_container_adapter['foo']);
        
        $this->illuminate_container_adapter->primitive('foo', 'baz');
    }
    
    /** @test */
    public function testOverwrittenFrozenServiceThrowsException()
    {
        $this->illuminate_container_adapter->factory(Foo::class, function () {
            return new Foo();
        });
        $this->illuminate_container_adapter->singleton(Bar::class, function () {
            return new Bar();
        });
        $this->illuminate_container_adapter->primitive('baz', 'biz');
        $this->illuminate_container_adapter->instance('foo.instance', new Foo());
        
        $foo = $this->illuminate_container_adapter[Foo::class];
        $this->assertInstanceOf(Foo::class, $foo);
        
        $this->illuminate_container_adapter->instance(Foo::class, $_foo = new Foo());
        $new_foo = $this->illuminate_container_adapter[Foo::class];
        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertSame($new_foo, $_foo);
        $this->assertNotSame($foo, $new_foo);
        
        // Can still be overwritten because it's not resolved yet.
        $this->illuminate_container_adapter->singleton(Bar::class, function () {
            return new Bar();
        });
        
        $bar = $this->illuminate_container_adapter[Bar::class];
        $this->assertInstanceOf(Bar::class, $bar);
        
        try {
            $this->illuminate_container_adapter->factory(Bar::class, function () {
                return new Bar();
            });
            $this->fail("No exception thrown");
        } catch (FrozenServiceException $e) {
            //
        }
        
        try {
            $this->illuminate_container_adapter->singleton(Bar::class, function () {
                return new Bar();
            });
            $this->fail("No exception thrown");
        } catch (FrozenServiceException $e) {
            //
        }
        
        $biz = $this->illuminate_container_adapter['baz'];
        $this->assertSame('biz', $biz);
        
        $this->illuminate_container_adapter->primitive('baz', 'boom');
        
        $boom = $this->illuminate_container_adapter['baz'];
        $this->assertSame('boom', $boom);
        
        $foo_as_instance = $this->illuminate_container_adapter['foo.instance'];
        $this->assertInstanceOf(Foo::class, $foo_as_instance);
        
        try {
            $this->illuminate_container_adapter->factory('foo.instance', function () {
                return new Foo();
            });
            $this->fail('No exception thrown');
        } catch (FrozenServiceException $e) {
            //
        }
    }
    
}