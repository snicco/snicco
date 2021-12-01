<?php

declare(strict_types=1);

namespace Tests\Core\unit\Application;

use stdClass;
use Snicco\Shared\ContainerAdapter;
use Tests\Codeception\shared\UnitTest;
use Snicco\Application\ManagesAliases;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Codeception\shared\helpers\CreateContainer;

class ManagesAliasesTest extends UnitTest
{
    
    use CreateContainer;
    
    private ManagesAliasImplementation $subject;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->subject = new ManagesAliasImplementation();
        $this->subject->container = $this->createContainer();
    }
    
    public function tearDown() :void
    {
        parent::tearDown();
    }
    
    /** @test */
    public function has_alias()
    {
        $this->assertFalse($this->subject->hasAlias('foo'));
        $this->subject->alias('foo', 'bar');
        $this->assertTrue($this->subject->hasAlias('foo'));
    }
    
    /** @test */
    public function get_alias()
    {
        $this->assertNull($this->subject->getAlias('foo'));
        $this->subject->alias('foo', 'bar', 'baz');
        $this->assertEquals([
            'name' => 'foo',
            'target' => 'bar',
            'method' => 'baz',
        ], $this->subject->getAlias('foo'));
    }
    
    /** @test */
    public function if_no_alias_is_registered_an_exception_is_thrown()
    {
        $this->expectExceptionMessage('Method: foo does not exist.');
        
        $this->subject->foo();
    }
    
    /** @test */
    public function closures_are_resolved_and_are_bound_to_the_current_class_instance()
    {
        $expected = 'foo';
        $this->subject->alias('test', fn() => $expected);
        
        $this->assertEquals($expected, $this->subject->test());
    }
    
    /** @test */
    public function aliases_can_be_used_to_resolve_objects_from_the_ioc_container()
    {
        $container = $this->createContainer();
        $container->bind('foobar', fn() => new stdClass());
        
        $this->subject->container = $container;
        
        $this->subject->alias('foo', 'foobar');
        
        $this->assertInstanceOf(stdClass::class, $this->subject->foo());
    }
    
    /** @test */
    public function methods_can_be_called_on_objects_in_the_ioc_container()
    {
        $container = $this->createContainer();
        $container->bind('foobar', fn() => new Foobar());
        
        $this->subject->container = $container;
        
        $this->subject->alias('foo', 'foobar', 'baz');
        
        $this->assertSame('BAZ', $this->subject->foo('baz'));
    }
    
    /** @test */
    public function services_can_be_resolved_from_the_container()
    {
        $this->assertInstanceOf(Foo::class, $this->subject->resolve(Foo::class));
    }
    
}

class ManagesAliasImplementation
{
    
    use ManagesAliases;
    
    public ContainerAdapter $container;
    
    public function resolve(string $key)
    {
        return $this->container->make($key);
    }
    
}

class Foobar
{
    
    public function baz($baz) :string
    {
        return strtoupper($baz);
    }
    
}