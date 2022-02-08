<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Testing;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Container\NotFoundExceptionInterface;
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

    abstract function createContainer(): DIContainer;

    /**
     * @test
     */
    final public function test_factory_returns_different_objects(): void
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

    /**
     * @test
     */
    final public function test_singleton_returns_the_same_object(): void
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

        $foo = new Foo();
        $container->instance('key', $foo);

        $bar = new Bar();
        $container->instance('key', $bar);

        $val = $container['key'];
        PHPUnit::assertSame($bar, $val);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    final public function test_primitive(): void
    {
        $container = $this->createContainer();

        $container->primitive('int', 1);
        PHPUnit::assertSame(1, $container['int']);

        $container->primitive('string', 'foo');
        PHPUnit::assertSame('foo', $container['string']);

        $container->primitive('array', []);
        PHPUnit::assertSame([], $container['array']);

        $container->primitive('true', true);
        PHPUnit::assertSame(true, $container['true']);

        $container->primitive('false', false);
        PHPUnit::assertSame(false, $container['false']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$value must be a scalar or an array of scalars. Got [stdClass].');
        $container->primitive('resource', new stdClass());
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_primitive_throws_for_non_scalar_array(): void
    {
        $container = $this->createContainer();

        $container->primitive('strings', ['foo', 'bar']);
        PHPUnit::assertSame(['foo', 'bar'], $container['strings']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$value must be a scalar or an array of scalars. Got [stdClass].');
        $container->primitive('strings', ['foo', new stdClass()]);
    }

    /**
     * @test
     */
    final public function test_overwritten_frozen_services_throws_exception(): void
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
        } catch (FrozenService $e) {
            //
        }

        try {
            $container->singleton(Bar::class, function () {
                return new Bar();
            });
            PHPUnit::fail('No exception thrown');
        } catch (FrozenService $e) {
            //
        }

        $foo_as_instance = $container['foo.instance'];
        PHPUnit::assertInstanceOf(Foo::class, $foo_as_instance);

        try {
            $container->factory('foo.instance', function () {
                return new Foo();
            });
            PHPUnit::fail('No exception thrown');
        } catch (FrozenService $e) {
            //
        }
    }

    /**
     * @test
     */
    final public function test_overwritten_primitive_throws_exception(): void
    {
        $container = $this->createContainer();

        $container->primitive('baz', 'biz');

        PHPUnit::assertEquals('biz', $container['baz']);

        try {
            $container->primitive('baz', 'boo');
            PHPUnit::fail('Overwriting a primitive value should throw an exception.');
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

        $container->singleton('foo', fn() => 'bar');
    }

    /**
     * @test
     */
    public function test_lock_throws_exception_for_factory(): void
    {
        $container = $this->createContainer();
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        $container->factory('foo', fn() => 'bar');
    }

    /**
     * @test
     */
    public function test_lock_throws_exception_for_instance(): void
    {
        $container = $this->createContainer();
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        $container->instance('foo', new stdClass());
    }

    /**
     * @test
     */
    public function test_lock_throws_for_primitive(): void
    {
        $container = $this->createContainer();
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        $container->primitive('foo', 'bar');
    }

    /**
     * @test
     */
    public function test_lock_throws_exception_for_array_set(): void
    {
        $container = $this->createContainer();
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        $container['foo'] = 'bar';
    }

    /**
     * @test
     */
    public function test_lock_throws_exception_for_array_unset(): void
    {
        $container = $this->createContainer();
        $container['foo'] = 'bar';
        $container->lock();

        $this->expectException(ContainerIsLocked::class);

        unset($container['foo']);
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
    public function test_make_throws_exception_if_class_string_id_does_not_return_class(): void
    {
        $container = $this->createContainer();
        $container[Foo::class] = 'foo';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Resolved value for class-string');
        $container[Foo::class];
    }

    /**
     * @test
     */
    public function test_offsetGet(): void
    {
        $container = $this->createContainer();

        $container->singleton(Foo::class, function () {
            return new Foo();
        });

        $foo1 = $container[Foo::class];
        $foo2 = $container->make(Foo::class);

        PHPUnit::assertSame($foo1, $foo2);
    }

    /**
     * @test
     * @psalm-suppress MissingClosureReturnType
     */
    public function test_offsetSet(): void
    {
        $container = $this->createContainer();
        $container['foo'] = ($foo = new Foo());

        // instance
        PHPUnit::assertSame($foo, $container['foo']);
        PHPUnit::assertSame($foo, $container['foo']);

        $bar = new Bar();
        $container['bar'] = function () use ($bar) {
            return $bar;
        };

        // instance
        PHPUnit::assertSame($bar, $container['bar']);
        PHPUnit::assertSame($bar, $container['bar']);


        $container['baz'] = 'biz';

        // primitive
        PHPUnit::assertSame('biz', $container['baz']);
        PHPUnit::assertSame('biz', $container['baz']);
    }

    /**
     * @test
     */
    public function test_offsetUnset(): void
    {
        $container = $this->createContainer();
        $container['foo'] = 'bar';

        PHPUnit::assertSame('bar', $container['foo']);

        unset($container['foo']);

        $this->expectException(NotFoundExceptionInterface::class);

        $container['foo'];
    }

    /**
     * @test
     */
    public function test_offsetExists(): void
    {
        $container = $this->createContainer();
        $container['foo'] = 'bar';

        PHPUnit::assertSame(true, isset($container['foo']));

        unset($container['foo']);

        PHPUnit::assertSame(false, isset($container['foo']));
    }

    /**
     * @test
     */
    public function test_has(): void
    {
        $container = $this->createContainer();
        $container['foo'] = 'bar';

        PHPUnit::assertSame(true, $container->has('foo'));

        unset($container['foo']);

        PHPUnit::assertSame(false, $container->has('foo'));
    }

}

class Foo
{
    public string $value = 'foo';
}

class Bar
{

}