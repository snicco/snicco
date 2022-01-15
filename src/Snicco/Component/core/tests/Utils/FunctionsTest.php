<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\Utils;

use Iterator;
use Countable;
use ArrayAccess;
use Traversable;
use InvalidArgumentException;
use Test\Helpers\CreateContainer;
use Psr\Container\ContainerInterface;
use Tests\Codeception\shared\UnitTest;
use Snicco\PimpleContainer\PimpleDIContainer;

use function Snicco\Component\Core\Utils\isInterface;

final class FunctionsTest extends UnitTest
{
    
    use CreateContainer;
    
    /** @test */
    public function test_isInterface_with_object()
    {
        $container = $this->createContainer();
        
        $this->assertFalse(isInterface($container, Countable::class));
        $this->assertTrue(isInterface($container, ContainerInterface::class));
        $this->assertTrue(isInterface($container, ArrayAccess::class));
        $this->assertFalse(isInterface($container, Traversable::class));
    }
    
    /** @test */
    public function test_is_interface_with_class_string()
    {
        $this->assertFalse(isInterface(PimpleDIContainer::class, Countable::class));
        $this->assertTrue(isInterface(PimpleDIContainer::class, ContainerInterface::class));
        $this->assertTrue(isInterface(PimpleDIContainer::class, ArrayAccess::class));
        $this->assertFalse(isInterface(PimpleDIContainer::class, Traversable::class));
    }
    
    /** @test */
    public function test_with_extended_interface()
    {
        $this->assertTrue(isInterface(new TestTraversable(), Iterator::class));
        $this->assertFalse(isInterface(new TestTraversable(), ArrayAccess::class));
        $this->assertTrue(isInterface(new TestTraversable(), Traversable::class));
        
        $this->assertTrue(isInterface(TestTraversable::class, Iterator::class));
        $this->assertFalse(isInterface(TestTraversable::class, ArrayAccess::class));
        $this->assertTrue(isInterface(TestTraversable::class, Traversable::class));
    }
    
    /** @test */
    public function test_true_with_interface_string()
    {
        $this->assertTrue(isInterface(ContainerInterface::class, ContainerInterface::class));
        $this->assertFalse(isInterface(ContainerInterface::class, ArrayAccess::class));
    }
    
    /** @test */
    public function test_false_for_missing_class()
    {
        $this->assertFalse(isInterface('Foo', ArrayAccess::class));
    }
    
    /** @test */
    public function test_exception_for_bad_interface()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Interface [Foo] does not exist.');
        
        $foo = isInterface(TestTraversable::class, 'Foo');
    }
    
    /** @test */
    public function test_with_child_interface_as_string()
    {
        $this->assertTrue(isInterface(Iterator::class, Traversable::class));
        $this->assertFalse(isInterface(Traversable::class, Iterator::class));
    }
    
}

class TestTraversable implements Iterator
{
    
    public function current()
    {
        // TODO: Implement current() method.
    }
    
    public function next()
    {
        // TODO: Implement next() method.
    }
    
    public function key()
    {
        // TODO: Implement key() method.
    }
    
    public function valid()
    {
        // TODO: Implement valid() method.
    }
    
    public function rewind()
    {
        // TODO: Implement rewind() method.
    }
    
}



