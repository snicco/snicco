<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\Testing;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Component\EventDispatcher\GenericEvent;
use Snicco\Component\EventDispatcher\ListenerFactory\NewableListenerFactory;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use stdClass;

use function property_exists;

/**
 * @internal
 */
final class TestableEventDispatcherTest extends TestCase
{
    private TestableEventDispatcher $fake_dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $dispatcher = new BaseEventDispatcher(new NewableListenerFactory());
        $this->fake_dispatcher = new TestableEventDispatcher($dispatcher);
    }

    /**
     * @test
     */
    public function the_fake_dispatcher_proxies_to_the_real_dispatcher_by_default(): void
    {
        $this->fake_dispatcher->listen(stdClass::class, function (stdClass $std): void {
            $std->val = 'BAR';
        });
        $event = new stdClass();
        $event->val = 'FOO';

        $res = $this->fake_dispatcher->dispatch($event);
        $this->assertInstanceOf(stdClass::class, $res);
        $this->assertSame('BAR', $res->val);
    }

    /**
     * @test
     */
    public function faked_events_will_not_be_proxied(): void
    {
        $this->fake_dispatcher->listen(stdClass::class, function (stdClass $std): void {
            $std->val = 'BAR';
        });
        $event = new stdClass();
        $event->val = 'FOO';

        $this->fake_dispatcher->fake(stdClass::class);

        $res = $this->fake_dispatcher->dispatch($event);
        $this->assertInstanceOf(stdClass::class, $res);
        $this->assertSame('FOO', $res->val);
    }

    /**
     * @test
     */
    public function all_events_can_be_faked(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (stdClass $std): void {
            $std->value = 'foo_event';
        });
        $this->fake_dispatcher->listen('bar_event', function (stdClass $std): void {
            $std->value = 'bar_event';
        });

        $std = new stdClass();
        $std->value = 'default';

        $this->fake_dispatcher->fake();

        $res = $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', [clone $std]));
        $payload = $res->payload();
        $this->assertTrue(isset($payload[0]) && $payload[0] instanceof stdClass);
        $this->assertSame('default', $payload[0]->value);

        $this->fake_dispatcher->dispatch(new GenericEvent('bar_event', [clone $std]));
        $payload = $res->payload();
        $this->assertTrue(isset($payload[0]) && $payload[0] instanceof stdClass);
        $this->assertSame('default', $payload[0]->value);
    }

    /**
     * @test
     */
    public function all_events_can_be_faked_as_an_alias_call(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (stdClass $std): void {
            $std->value = 'foo_event';
        });
        $this->fake_dispatcher->listen('bar_event', function (stdClass $std): void {
            $std->value = 'bar_event';
        });

        $std = new stdClass();
        $std->value = 'default';

        $this->fake_dispatcher->fakeAll();

        $res = $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', [clone $std]));
        $payload = $res->payload();
        $this->assertTrue(isset($payload[0]) && $payload[0] instanceof stdClass);
        $this->assertSame('default', $payload[0]->value);

        $this->fake_dispatcher->dispatch(new GenericEvent('bar_event', [clone $std]));
        $payload = $res->payload();
        $this->assertTrue(isset($payload[0]) && $payload[0] instanceof stdClass);
        $this->assertSame('default', $payload[0]->value);
    }

    /**
     * @test
     */
    public function test_faked_events_count_as_dispatched(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (): void {
        });

        $this->fake_dispatcher->fake('foo_event');

        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event'));

        $this->fake_dispatcher->assertDispatched('foo_event');
    }

    /**
     * @test
     */
    public function all_but_some_specified_events_can_be_faked(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (stdClass $std): void {
            $std->value = 'foo_event';
        });

        $this->fake_dispatcher->listen('bar_event', function (stdClass $std): void {
            $std->value = 'bar_event';
        });

        $this->fake_dispatcher->fakeExcept('bar_event');

        $std = new stdClass();
        $std->value = 'default';

        $res = $this->fake_dispatcher->dispatch(new stdClassEvent('foo_event', $std));
        $this->assertSame('default', $res->payload()->value);

        $std = new stdClass();
        $std->value = 'default';

        $res = $this->fake_dispatcher->dispatch(new stdClassEvent('bar_event', $std));
        $this->assertSame('bar_event', $res->payload()->value);
    }

    /**
     * @test
     */
    public function fake_except_throws_exception_for_empty_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cant be an empty array');

        $this->fake_dispatcher->fakeExcept([]);
    }

    /**
     * @test
     */
    public function the_return_type_is_correct_for_generic_object_events_if_events_are_faked(): void
    {
        $this->fake_dispatcher->listen(stdClass::class, function (stdClass $event): void {
            $event->value = 'BAR';
        });

        $this->fake_dispatcher->fake(stdClass::class);

        $event = new stdClass();
        $event->val = 'FOO';

        $res = $this->fake_dispatcher->dispatch($event);
        $this->assertSame($res, $event);
    }

    /**
     * @test
     */
    public function the_return_type_is_correct_for_object_events(): void
    {
        $this->fake_dispatcher->fake('foo_event');
        $result = $this->fake_dispatcher->dispatch($event = new GenericEvent('foo_event', ['FOO', 'BAR']));

        $this->assertSame($event, $result);
        $this->assertSame(['FOO', 'BAR'], $event->payload());
    }

    /**
     * @test
     */
    public function events_that_are_not_faked_explicitly_are_passed_to_the_real_dispatcher(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (stdClass $stdClass): void {
            $stdClass->value = 'foo_event';
        });
        $this->fake_dispatcher->listen('bar_event', function (stdClass $stdClass): void {
            $stdClass->value = 'bar_event';
        });

        $this->fake_dispatcher->fake('foo_event');

        $this->fake_dispatcher->dispatch(new stdClassEvent('foo_event', $std = new stdClass()));
        $this->assertFalse(property_exists($std, 'value'));

        $this->fake_dispatcher->dispatch(new stdClassEvent('bar_event', $std = new stdClass()));
        $this->assertTrue(property_exists($std, 'value'));
        $this->assertSame('bar_event', $std->value);
    }

    /**
     * @test
     */
    public function multiple_events_can_be_faked(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (stdClass $stdClass): void {
            $stdClass->value = 'foo_event';
        });
        $this->fake_dispatcher->listen('bar_event', function (stdClass $stdClass): void {
            $stdClass->value = 'bar_event';
        });

        $this->fake_dispatcher->fake(['foo_event', 'bar_event']);

        $this->fake_dispatcher->dispatch(new stdClassEvent('foo_event', $std = new stdClass()));
        $this->assertFalse(property_exists($std, 'value'));

        $this->fake_dispatcher->dispatch(new stdClassEvent('bar_event', $std = new stdClass()));
        $this->assertFalse(property_exists($std, 'value'));
    }

    /**
     * @test
     */
    public function the_fake_dispatcher_can_be_reset(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (stdClass $stdClass): void {
            $stdClass->value = 'foo_event';
        });

        $this->fake_dispatcher->dispatch(new stdClassEvent('foo_event', $std = new stdClass()));
        $this->assertTrue(property_exists($std, 'value'));

        $this->fake_dispatcher->assertDispatched('foo_event');

        $this->fake_dispatcher->resetDispatchedEvents();

        $this->fake_dispatcher->assertNotDispatched('foo_event');
    }

    /**
     * @test
     */
    public function test_remove(): void
    {
        $dispatcher = new BaseEventDispatcher();
        $dispatcher->listen('foo', function (): void {
            throw new Exception('listener not removed.');
        });

        $fake = new TestableEventDispatcher($dispatcher);

        $fake->remove('foo');

        $fake->dispatch(new GenericEvent('foo'));

        $fake->assertDispatched('foo');
    }

    /**
     * @test
     */
    public function test_subscribe(): void
    {
        $dispatcher = new BaseEventDispatcher();

        $fake = new TestableEventDispatcher($dispatcher);

        $fake->subscribe(TestableEventDispatcherTestSubscriber::class);

        $std = new stdClass();
        $std->value = 'foo';
        $res = $fake->dispatch($std);

        $this->assertSame('changed', $res->value);
    }
}

final class stdClassEvent implements Event
{
    private string $name;

    private stdClass $payload;

    public function __construct(string $name, stdClass $payload)
    {
        $this->name = $name;
        $this->payload = $payload;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function payload(): stdClass
    {
        return $this->payload;
    }
}

final class TestableEventDispatcherTestSubscriber implements EventSubscriber
{
    public function foo(stdClass $stdClass): void
    {
        $stdClass->value = 'changed';
    }

    public static function subscribedEvents(): array
    {
        return [
            stdClass::class => 'foo',
        ];
    }
}
