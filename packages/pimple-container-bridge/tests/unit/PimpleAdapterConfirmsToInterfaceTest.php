<?php

declare(strict_types=1);

namespace Tests\PimpleContainer\unit;

use Pimple\Container;
use Tests\Codeception\shared\UnitTest;
use Snicco\PimpleContainer\PimpleContainerAdapter;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Codeception\shared\TestDependencies\Bar;

final class PimpleAdapterConfirmsToInterfaceTest extends UnitTest
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
    
}