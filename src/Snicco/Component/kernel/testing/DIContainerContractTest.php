<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Testing;

use LogicException;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Exception\ContainerIsLocked;
use Snicco\Component\Kernel\Exception\FrozenService;
use stdClass;

/**
 * @codeCoverageIgnore
 */
trait DIContainerContractTest
{
    private DIContainer $container;

    abstract public function createContainer(): DIContainer;

    /**
     * @test
     */
    final public function test_factory_returns_different_objects(): void
    {
        $container = $this->createContainer();

        $container->factory(Foo::class, fn (): Foo => new Foo());

        $foo1 = $container[Foo::class];
        $foo2 = $container->get(Foo::class);

        PHPUnit::assertInstanceOf(Foo::class, $foo1);
        PHPUnit::assertInstanceOf(Foo::class, $foo2);
        PHPUnit::assertNotSame($foo1, $foo2);
    }

    /**
     * @test
     */
    final public function test_singleton_returns_the_same_object(): void
    {
        $container = $this->createContainer();

        $container->shared(Foo::class, fn (): Foo => new Foo());

        $foo1 = $container[Foo::class];
        $foo2 = $container->get(Foo::class);

        PHPUnit::assertInstanceOf(Foo::class, $foo1);
        PHPUnit::assertInstanceOf(Foo::class, $foo2);
        PHPUnit::assertSame($foo1, $foo2);
    }

    /**
     * @test
     */
    final public function test_instance_returns_the_same_object(): void
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

    /**
     * @test
     */
    final public function services_can_be_redefined(): void
    {
        $container = $this->createContainer();

        $foo1 = new Foo();
        $container->instance(Foo::class, $foo1);

        $foo2 = new Foo();
        $container->instance(Foo::class, $foo2);

        $val = $container[Foo::class];
        PHPUnit::assertSame($foo2, $val);
    }

    /**
     * @test
     */
    final public function test_overwritten_frozen_services_throws_exception(): void
    {
        $container = $this->createContainer();

        $container->factory(Foo::class, fn (): Foo => new Foo());
        $container->shared(Bar::class, fn (): Bar => new Bar());
        $container->instance(Baz::class, new Baz());

        $foo = $container[Foo::class];
        PHPUnit::assertInstanceOf(Foo::class, $foo);

        $container->instance(Foo::class, $_foo = new Foo());
        $new_foo = $container[Foo::class];
        PHPUnit::assertInstanceOf(Foo::class, $foo);
        PHPUnit::assertSame($new_foo, $_foo);
        PHPUnit::assertNotSame($foo, $new_foo);

        // Can still be overwritten because it's not resolved yet.
        $container->shared(Bar::class, fn (): Bar => new Bar());

        $bar = $container[Bar::class];
        PHPUnit::assertInstanceOf(Bar::class, $bar);

        try {
            $container->factory(Bar::class, fn (): Bar => new Bar());
            PHPUnit::fail('No exception thrown');
        } catch (FrozenService $e) {
        }

        try {
            $container->shared(Bar::class, fn (): Bar => new Bar());
            PHPUnit::fail('No exception thrown');
        } catch (FrozenService $e) {
        }

        $baz = $container[Baz::class];
        PHPUnit::assertInstanceOf(Baz::class, $baz);

        try {
            $container->factory(Baz::class, fn (): Baz => new Baz());
            PHPUnit::fail('No exception thrown');
        } catch (FrozenService $e) {
        }
    }

    /**
     * @test
     */
    public function test_lock_throws_exception_for_singleton(): void
    {
        $container = $this->createContainer();
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        $container->shared(Foo::class, fn (): Foo => new Foo());
    }

    /**
     * @test
     */
    public function test_lock_throws_exception_for_factory(): void
    {
        $container = $this->createContainer();
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        $container->factory(Foo::class, fn (): Foo => new Foo());
    }

    /**
     * @test
     */
    public function test_lock_throws_exception_for_instance(): void
    {
        $container = $this->createContainer();
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        $container->instance(stdClass::class, new stdClass());
    }

    /**
     * @test
     */
    public function test_lock_throws_exception_for_array_set(): void
    {
        $container = $this->createContainer();
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        $container[stdClass::class] = new stdClass();
    }

    /**
     * @test
     */
    public function test_lock_throws_exception_for_array_unset(): void
    {
        $container = $this->createContainer();
        $container[stdClass::class] = new stdClass();
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        unset($container[stdClass::class]);
    }

    /**
     * @test
     */
    public function test_make_returns_the_correct_class_instance(): void
    {
        $container = $this->createContainer();
        $foo = new Foo();
        $foo->value = 'FOO';
        $container[Foo::class] = $foo;

        $resolved = $container[Foo::class];
        PHPUnit::assertSame($foo, $resolved);
        PHPUnit::assertSame($foo, $container->make(Foo::class));
    }

    /**
     * @test
     */
    public function test_make_throws_exception_if_class_different_class_is_returned(): void
    {
        $container = $this->createContainer();
        $container[Foo::class] = new Bar();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected an instance of Snicco\Component\Kernel\Testing\Foo');
        // @noRector
        $container->make(Foo::class);
    }

    /**
     * @test
     */
    public function test_offsetGet(): void
    {
        $container = $this->createContainer();

        $container->shared(Foo::class, fn (): Foo => new Foo());

        $foo1 = $container[Foo::class];
        $foo2 = $container->make(Foo::class);

        PHPUnit::assertSame($foo1, $foo2);
    }

    /**
     * @test
     */
    public function test_offsetSet(): void
    {
        $container = $this->createContainer();
        $container[Foo::class] = new Foo();
        $foo = $container[Foo::class];

        // instance
        PHPUnit::assertSame($foo, $container[Foo::class]);

        $bar = new Bar();
        $container[Bar::class] = fn (): Bar => $bar;

        // instance
        PHPUnit::assertSame($bar, $container[Bar::class]);
    }

    /**
     * @test
     */
    public function test_offsetUnset(): void
    {
        $container = $this->createContainer();
        $container[stdClass::class] = new stdClass();
        $std = $container[stdClass::class];

        PHPUnit::assertSame($std, $container[stdClass::class]);

        unset($container[stdClass::class]);

        PHPUnit::assertFalse($container->has(stdClass::class));
    }

    /**
     * @test
     */
    public function test_offsetExists(): void
    {
        $container = $this->createContainer();
        $container[stdClass::class] = new stdClass();

        PHPUnit::assertSame(true, isset($container[stdClass::class]));

        unset($container[stdClass::class]);

        PHPUnit::assertSame(false, isset($container[stdClass::class]));
    }

    /**
     * @test
     */
    public function test_has(): void
    {
        $container = $this->createContainer();
        $container[stdClass::class] = new stdClass();

        PHPUnit::assertSame(true, $container->has(stdClass::class));

        unset($container[stdClass::class]);

        PHPUnit::assertSame(false, $container->has(stdClass::class));
    }

    /**
     * @test
     */
    public function test_with_callables(): void
    {
        $container = $this->createContainer();
        $container->factory(Foo::class, [$this, 'getFoo']);
        $container->shared(Bar::class, [$this, 'getBar']);

        PHPUnit::assertSame('callable_foo', $container[Foo::class]->value);
        PHPUnit::assertSame('callable_bar', $container[Bar::class]->value);
    }

    public function getBar(): Bar
    {
        $bar = new Bar();
        $bar->value = 'callable_bar';

        return $bar;
    }

    public function getFoo(): Foo
    {
        $foo = new Foo();
        $foo->value = 'callable_foo';

        return $foo;
    }
}

class Foo
{
    public string $value = 'foo';
}

class Bar
{
    public string $value = 'bar';
}

class Baz
{
}
