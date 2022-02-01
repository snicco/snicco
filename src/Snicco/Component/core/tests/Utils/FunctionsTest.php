<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\Utils;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReturnTypeWillChange;
use Traversable;

use function Snicco\Component\Core\Utils\isInterface;

final class FunctionsTest extends TestCase
{

    /**
     * @test
     */
    public function test_isInterface_with_object(): void
    {
        $subject = new TestSubject();

        $this->assertTrue(isInterface($subject, Countable::class));

        $this->assertFalse(isInterface($subject, JsonSerializable::class));
    }

    /**
     * @test
     */
    public function test_is_interface_with_class_string(): void
    {
        $this->assertTrue(isInterface(TestSubject::class, Countable::class));

        $this->assertFalse(isInterface(TestSubject::class, JsonSerializable::class));
    }

    /**
     * @test
     */
    public function test_with_extended_interface(): void
    {
        $this->assertTrue(isInterface(new TestTraversable(), Iterator::class));
        $this->assertFalse(isInterface(new TestTraversable(), ArrayAccess::class));
        $this->assertTrue(isInterface(new TestTraversable(), Traversable::class));

        $this->assertTrue(isInterface(TestTraversable::class, Iterator::class));
        $this->assertFalse(isInterface(TestTraversable::class, ArrayAccess::class));
        $this->assertTrue(isInterface(TestTraversable::class, Traversable::class));
    }

    /**
     * @test
     */
    public function test_true_with_interface_string(): void
    {
        $this->assertTrue(isInterface(ContainerInterface::class, ContainerInterface::class));
        $this->assertFalse(isInterface(ContainerInterface::class, ArrayAccess::class));
    }

    /**
     * @test
     */
    public function test_false_for_missing_class(): void
    {
        $this->assertFalse(isInterface('Foo', ArrayAccess::class));
    }

    /**
     * @test
     */
    public function test_exception_for_bad_interface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Interface [Foo] does not exist.');

        isInterface(TestTraversable::class, 'Foo');
    }

    /**
     * @test
     */
    public function test_with_child_interface_as_string(): void
    {
        $this->assertTrue(isInterface(Iterator::class, Traversable::class));
        $this->assertFalse(isInterface(Traversable::class, Iterator::class));
    }

}

class TestSubject implements Countable
{

    public function count(): int
    {
    }

}

class TestTraversable implements Iterator
{

    #[ReturnTypeWillChange]
    public function current()
    {
    }

    public function next(): void
    {
    }

    #[ReturnTypeWillChange]
    public function key()
    {
    }

    public function valid(): bool
    {
    }

    public function rewind(): void
    {
    }

}



