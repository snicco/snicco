<?php

declare(strict_types=1);

namespace Tests\Core\unit\Support;

use Snicco\Shared\ContainerAdapter;
use Tests\Codeception\shared\UnitTest;
use Snicco\Support\ReflectionDependencies;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Codeception\shared\TestDependencies\Bar;
use Tests\Codeception\shared\helpers\CreateContainer;

class ReflectionDependenciesTest extends UnitTest
{
    
    use CreateContainer;
    
    private ContainerAdapter $container;
    private ReflectionDependencies $route_action_dependencies;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->container = $this->createContainer();
        $this->route_action_dependencies = new ReflectionDependencies($this->container);
    }
    
    /**
     * @test
     */
    public function testMethodWithNoDependencies()
    {
        $args = $this->route_action_dependencies->build(
            [TestRouteAction::class, 'withNoClassDependencies'],
            ['1', '2']
        );
        
        $this->assertSame(['1', '2'], $args);
    }
    
    /** @test */
    public function testMethodWithClassDependency()
    {
        $this->container->instance(
            Foo::class,
            $foo_class = new Foo()
        );
        
        $args = $this->route_action_dependencies->build(
            [TestRouteAction::class, 'withClassDependencies'],
            ['1', '2']
        );
        
        $this->assertSame([$foo_class, '1', '2'], $args);
    }
    
    /** @test */
    public function testWithClassAlreadyInParameter()
    {
        $this->container->instance(
            Foo::class,
            $foo_class = new Foo()
        );
        
        $bar = new Bar();
        $bar->bar = 'custom_value';
        
        $args = $this->route_action_dependencies->build(
            [TestRouteAction::class, 'withClassInParameters'],
            [$bar, '1', '2']
        );
        
        $this->assertSame([$bar, $foo_class, '1', '2'], $args);
        $this->assertSame('custom_value', $bar->bar);
    }
    
    /** @test */
    public function testWithClassAlreadyInParameterWithDifferentOrder()
    {
        $this->container->instance(
            Foo::class,
            $foo_class = new Foo()
        );
        
        $bar = new Bar();
        $bar->bar = 'custom_value';
        
        $args = $this->route_action_dependencies->build(
            [TestRouteAction::class, 'withClassInParameters'],
            ['1', '2', $bar]
        );
        
        $this->assertSame([$bar, $foo_class, '1', '2'], $args);
        $this->assertSame('custom_value', $bar->bar);
    }
    
    /** @test */
    public function testWithClosure()
    {
        $this->container->instance(
            Foo::class,
            $foo_class = new Foo()
        );
        
        $bar = new Bar();
        $bar->bar = 'custom_value';
        
        $args = $this->route_action_dependencies->build(
            function (Bar $class1, Foo $class2, $dep1, $dep2) { },
            [$bar, '1', '2']
        );
        
        $this->assertSame([$bar, $foo_class, '1', '2'], $args);
        $this->assertSame('custom_value', $bar->bar);
    }
    
    /** @test */
    public function testWithDefaultValueAvailable()
    {
        $bar = new Bar();
        $foo = new Foo();
        
        $args = $this->route_action_dependencies->build(
            [TestRouteAction::class, 'withDefaultValue'],
            [$bar, $foo, '1']
        );
        
        $this->assertSame([$bar, $foo, '1',], $args);
    }
    
    /** @test */
    public function testObjectThatArePassedAsAPayloadAreNotFilteredOut()
    {
        $this->container->instance(Foo::class, $foo = new Foo());
        
        $args = $this->route_action_dependencies->build(
            [TestRouteAction::class, 'withObjectValue'],
            ['val', $bar = new Bar()]
        );
        
        $this->assertSame([$foo, 'val', $bar], $args);
    }
    
}

class TestRouteAction
{
    
    public function withNoClassDependencies($foo, $bar)
    {
    }
    
    public function withClassDependencies(Foo $class, $foo, $bar)
    {
    }
    
    public function withClassInParameters(Bar $class1, Foo $class2, $foo, $bar)
    {
    }
    
    public function withDefaultValue(Bar $class1, Foo $class2, $foo, $bar = 'bar')
    {
    }
    
    public function onlyDefaultValues(string $foo = 'foo', string $bar = 'bar')
    {
    }
    
    public function withObjectValue(Foo $foo, string $val, Bar $bar)
    {
    }
    
}