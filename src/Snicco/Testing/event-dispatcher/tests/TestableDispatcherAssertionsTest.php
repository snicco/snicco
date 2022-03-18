<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\Testing;

use Closure;
use LogicException;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\GenericEvent;
use Snicco\Component\EventDispatcher\ListenerFactory\NewableListenerFactory;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use stdClass;

use function get_class;
use function sprintf;

/**
 * @internal
 */
final class TestableDispatcherAssertionsTest extends TestCase
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
    public function assert_nothing_dispatched_can_pass(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (): void {
        });

        $this->fake_dispatcher->assertNotingDispatched();
    }

    /**
     * @test
     */
    public function assert_nothing_dispatched_can_fail(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (): void {
        });

        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));

        $this->assertFailsWithMessageStarting(
            '1 event[s] dispatched',
            fn () => $this->fake_dispatcher->assertNotingDispatched()
        );
    }

    /**
     * @test
     */
    public function assert_dispatched_can_pass(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (): void {
        });
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));

        $this->fake_dispatcher->assertDispatched('foo_event');
    }

    /**
     * @test
     */
    public function assert_dispatched_can_fail(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (): void {
        });

        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was not dispatched',
            function (): void {
                $this->fake_dispatcher->assertDispatched('foo_event');
            }
        );
    }

    /**
     * @test
     */
    public function assert_dispatched_can_pass_with_truthful_condition_about_the_event(): void
    {
        $this->fake_dispatcher->listen('foo_event', function (): void {
        });
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO', 'BAR']));

        $this->fake_dispatcher->assertDispatched(
            'foo_event',
            fn (string $foo, string $bar): bool => 'FOO' === $foo && 'BAR' === $bar
        );
    }

    /**
     * @test
     */
    public function assert_dispatched_can_fail_when_the_event_was_not_dispatched(): void
    {
        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was not dispatched',
            function (): void {
                $this->fake_dispatcher->assertDispatched(
                    'foo_event',
                    fn (string $foo, string $bar): bool => 'FOO' === $foo && 'BAR' === $bar
                );
            }
        );
    }

    /**
     * @test
     */
    public function assert_dispatched_can_fail_when_the_event_was_dispatched_but_the_additional_condition_didnt_match(
    ): void {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO', 'BAZ']));

        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched but the provided condition did not pass.',
            function (): void {
                $this->fake_dispatcher->assertDispatched(
                    'foo_event',
                    fn (string $foo, string $bar): bool => 'FOO' === $foo && 'BAR' === $bar
                );
            }
        );
    }

    /**
     * @test
     */
    public function assert_dispatched_can_pass_with_object_event(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->fake_dispatcher->assertDispatched(
            EventStub::class,
            fn (EventStub $event_stub): bool => 'FOO' === $event_stub->val1 && 'BAR' === $event_stub->val2
        );
    }

    /**
     * @test
     */
    public function assert_dispatched_can_fail_object_event(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->assertFailsWithMessageStarting(
            sprintf('The event [%s] was dispatched but the provided condition did not pass.', EventStub::class),
            function (): void {
                $this->fake_dispatcher->assertDispatched(
                    EventStub::class,
                    fn (EventStub $event_stub): bool => 'FOO' === $event_stub->val1 && 'BAZ' === $event_stub->val2
                );
            }
        );
    }

    /**
     * @test
     */
    public function assert_dispatched_can_pass_with_typehinted_closure_only(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->fake_dispatcher->assertDispatched(
            fn (EventStub $event_stub): bool => 'FOO' === $event_stub->val1 && 'BAR' === $event_stub->val2
        );
    }

    /**
     * @test
     */
    public function assert_dispatched_can_fail_with_typehinted_closure_only(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->assertFailsWithMessageStarting(
            sprintf('The event [%s] was dispatched but the provided condition did not pass', EventStub::class),
            function (): void {
                $this->fake_dispatcher->assertDispatched(
                    fn (EventStub $event_stub): bool => 'FOO' === $event_stub->val1
                        && 'BAZ' === $event_stub->val2
                );
            },
        );
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidScalarArgument
     */
    public function assert_dispatched_fails_if_closure_does_not_return_boolean(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('did not return bool');

        $this->fake_dispatcher->assertDispatched(fn (EventStub $event): string => get_class($event));
    }

    /**
     * @test
     */
    public function assert_dispatched_times_can_pass(): void
    {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));
        $this->fake_dispatcher->assertDispatchedTimes('foo_event', 2);
    }

    /**
     * @test
     */
    public function assert_dispatched_times_can_fail(): void
    {
        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched [3] time[s].',
            function (): void {
                $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));
                $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));
                $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));
                $this->fake_dispatcher->assertDispatchedTimes('foo_event', 2);
            }
        );
    }

    /**
     * @test
     */
    public function assert_not_dispatched_can_pass(): void
    {
        $this->fake_dispatcher->assertNotDispatched('foo_event');
    }

    /**
     * @test
     */
    public function assert_not_dispatched_can_fail(): void
    {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event'));

        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched [1] time[s].',
            function (): void {
                $this->fake_dispatcher->assertNotDispatched('foo_event');
            }
        );
    }

    /**
     * @test
     */
    public function assert_not_dispatched_can_pass_with_wrong_condition(): void
    {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['BAR']));

        $this->fake_dispatcher->assertNotDispatched('foo_event', fn ($val): bool => 'FOO' === $val);
    }

    /**
     * @test
     */
    public function assert_not_dispatched_will_fail_with_truthful_condition(): void
    {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['BAR']));

        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched and the condition passed.',
            function (): void {
                $this->fake_dispatcher->assertNotDispatched('foo_event', fn ($val): bool => 'BAR' === $val);
            }
        );
    }

    /**
     * @test
     */
    public function assert_not_dispatched_can_pass_with_typehinted_closure_only(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAZ'));

        $this->fake_dispatcher->assertNotDispatched(
            fn (EventStub $event_stub): bool => 'FOO' === $event_stub->val1 && 'BAR' === $event_stub->val2
        );
    }

    /**
     * @test
     */
    public function assert_dispatched_accepts_a_generic_object_as_the_closure_argument(): void
    {
        $event = new stdClass();
        $event->foo = 'FOO';
        $event->bar = 'BAR';

        $this->fake_dispatcher->dispatch($event);

        $this->fake_dispatcher->assertDispatched(
            fn (stdClass $event): bool => 'FOO' === $event->foo && 'BAR' === $event->bar
        );
    }

    /**
     * @test
     */
    public function assert_not_dispatched_will_fail_if_event_object_was_dispatched_and_condition_passed(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAZ'));

        $this->assertFailsWithMessageStarting(
            sprintf('The event [%s] was dispatched and the condition passed.', EventStub::class),
            fn () => $this->fake_dispatcher->assertNotDispatched(
                fn (EventStub $event_stub): bool => 'FOO' === $event_stub->val1 && 'BAZ' === $event_stub->val2
            )
        );
    }

    private function assertFailsWithMessageStarting(string $message, Closure $closure): void
    {
        try {
            $closure();
            PHPUnit::fail('The test was expected to fail a PHPUnit assertion.');
        } catch (ExpectationFailedException $e) {
            PHPUnit::assertStringStartsWith(
                $message,
                $e->getMessage(),
                'The test failed but the failure message was not as expected.'
            );
        }
    }
}

final class EventStub implements Event
{
    use ClassAsName;
    use ClassAsPayload;

    public string $val1;

    public string $val2;

    public function __construct(string $foo, string $bar)
    {
        $this->val1 = $foo;
        $this->val2 = $bar;
    }
}
