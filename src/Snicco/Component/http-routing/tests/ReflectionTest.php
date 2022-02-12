<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Snicco\Component\HttpRouting\IsInterfaceString;
use Traversable;

final class ReflectionTest extends TestCase
{


    /**
     * @test
     */
    public function test_is_interface_with_class_string(): void
    {
        $this->assertTrue(IsInterfaceString::check(TestSubject::class, Countable::class));

        $this->assertFalse(IsInterfaceString::check(TestSubject::class, JsonSerializable::class));
    }

    /**
     * @test
     */
    public function test_with_extended_interface(): void
    {
        $this->assertTrue(IsInterfaceString::check(TestTraversable::class, Iterator::class));
        $this->assertFalse(IsInterfaceString::check(TestTraversable::class, ArrayAccess::class));
        $this->assertTrue(IsInterfaceString::check(TestTraversable::class, Traversable::class));
    }

    /**
     * @test
     */
    public function test_true_with_interface_string(): void
    {
        $this->assertTrue(IsInterfaceString::check(ContainerInterface::class, ContainerInterface::class));
        $this->assertFalse(IsInterfaceString::check(ContainerInterface::class, ArrayAccess::class));
    }

    /**
     * @test
     */
    public function test_false_for_missing_class(): void
    {
        $this->assertFalse(IsInterfaceString::check('Foo', ArrayAccess::class));
    }

    /**
     * @test
     */
    public function test_exception_for_bad_interface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Interface [Foo] does not exist.');

        IsInterfaceString::check(TestTraversable::class, 'Foo');
    }

    /**
     * @test
     */
    public function test_with_child_interface_as_string(): void
    {
        $this->assertTrue(IsInterfaceString::check(Iterator::class, Traversable::class));
        $this->assertFalse(IsInterfaceString::check(Traversable::class, Iterator::class));
    }

    /**
     * @test
     * @psalm-suppress UnusedClosureParam
     */
    public function test_firstParameterType_with_closure(): void
    {
        $this->assertSame(
            'string',
            IsInterfaceString::firstParameterType(function (string $foo): void {
                //
            })
        );
    }

    /**
     * @test
     */
    public function test_firstParameterType_with_class_string_uses_constructor(): void
    {
        $this->assertSame('string', IsInterfaceString::firstParameterType(ClassWithConstructor::class));
    }


}

class NoConstructor
{

}

class ClassWithConstructor
{

    public function __construct(string $foo)
    {
    }

    public function someMethod(string $foo, string $bar): void
    {
    }

}

class TestSubject implements Countable
{

    public function count(): int
    {
        return 0;
    }

}

class TestTraversable implements Iterator
{

    #[ReturnTypeWillChange]
    /**
     * @return void
     */
    public function current()
    {
    }

    public function next(): void
    {
    }

    #[ReturnTypeWillChange]
    /**
     * @return void
     */
    public function key()
    {
    }

    public function valid(): bool
    {
        return true;
    }

    public function rewind(): void
    {
        //
    }

}