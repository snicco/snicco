<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\GenericEvent;
use Snicco\Component\EventDispatcher\ListenerFactory\NewableListenerFactory;
use Snicco\Component\EventDispatcher\TestableEventDispatcher;
use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;
use Snicco\Component\EventDispatcher\Tests\fixtures\AssertPHPUnitFailures;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\EventStub;
use stdClass;

use function sprintf;

final class TestableDispatcherAssertionsTest extends TestCase
{

    use AssertPHPUnitFailures;
    use AssertListenerResponse;

    private TestableEventDispatcher $fake_dispatcher;

    /**
     * @test
     */
    public function assertNothingDispatched_can_pass(): void
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });

        $this->fake_dispatcher->assertNotingDispatched();
    }

    /**
     * @test
     */
    public function assertNothingDispatched_can_fail(): void
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });

        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));

        $this->assertFailsWithMessageStarting(
            '1 event[s] dispatched',
            fn() => $this->fake_dispatcher->assertNotingDispatched()
        );
    }

    /**
     * @test
     */
    public function assertDispatched_can_pass(): void
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));

        $this->fake_dispatcher->assertDispatched('foo_event');
    }

    /**
     * @test
     */
    public function assertDispatched_can_fail(): void
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });

        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was not dispatched',
            function () {
                $this->fake_dispatcher->assertDispatched('foo_event');
            }
        );
    }

    /**
     * @test
     */
    public function assertDispatched_can_pass_with_truthful_condition_about_the_event(): void
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO', 'BAR']));

        $this->fake_dispatcher->assertDispatched('foo_event', function ($foo, $bar) {
            return $foo === 'FOO' && $bar === 'BAR';
        });
    }

    /**
     * @test
     */
    public function assertDispatched_can_fail_when_the_event_was_not_dispatched(): void
    {
        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was not dispatched',
            function () {
                $this->fake_dispatcher->assertDispatched('foo_event', function ($foo, $bar) {
                    return $foo === 'FOO' && $bar === 'BAR';
                });
            }
        );
    }

    /**
     * @test
     */
    public function assertDispatched_can_fail_when_the_event_was_dispatched_but_the_additional_condition_didnt_match(
    ): void
    {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO', 'BAZ']));

        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched but the provided condition did not pass.',
            function () {
                $this->fake_dispatcher->assertDispatched('foo_event', function ($foo, $bar) {
                    return $foo === 'FOO' && $bar === 'BAR';
                });
            }
        );
    }

    /**
     * @test
     */
    public function assertDispatched_can_pass_with_object_event(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->fake_dispatcher->assertDispatched(
            EventStub::class,
            function (EventStub $event_stub) {
                return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAR';
            }
        );
    }

    /**
     * @test
     */
    public function assertDispatched_can_fail_object_event(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->assertFailsWithMessageStarting(
            sprintf(
                'The event [%s] was dispatched but the provided condition did not pass.',
                EventStub::class
            ),
            function () {
                $this->fake_dispatcher->assertDispatched(
                    EventStub::class,
                    function (EventStub $event_stub) {
                        return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAZ';
                    }
                );
            }
        );
    }

    /**
     * @test
     */
    public function assertDispatched_can_pass_with_typehinted_closure_only(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->fake_dispatcher->assertDispatched(
            function (EventStub $event_stub) {
                return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAR';
            }
        );
    }

    /**
     * @test
     */
    public function assertDispatched_can_fail_with_typehinted_closure_only(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->assertFailsWithMessageStarting(
            sprintf(
                'The event [%s] was dispatched but the provided condition did not pass',
                EventStub::class
            ),
            function () {
                $this->fake_dispatcher->assertDispatched(
                    function (EventStub $event_stub) {
                        return $event_stub->val1 === 'FOO'
                            && $event_stub->val2 === 'BAZ';
                    }
                );
            },
        );
    }

    /**
     * @test
     */
    public function assertDispatched_fails_if_closure_does_not_return_boolean(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('did not return bool');

        $this->fake_dispatcher->assertDispatched(
            function (EventStub $event_stub) {
                return '1';
            }
        );
    }

    /**
     * @test
     */
    public function assertDispatchedTimes_can_pass(): void
    {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOO']));
        $this->fake_dispatcher->assertDispatchedTimes('foo_event', 2);
    }

    /**
     * @test
     */
    public function assertDispatchedTimes_can_fail(): void
    {
        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched [3] time[s].',
            function () {
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
    public function assertNotDispatched_can_pass(): void
    {
        $this->fake_dispatcher->assertNotDispatched('foo_event');
    }

    /**
     * @test
     */
    public function assertNotDispatched_can_fail(): void
    {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event'));

        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched [1] time[s].',
            function () {
                $this->fake_dispatcher->assertNotDispatched('foo_event');
            }
        );
    }

    /**
     * @test
     */
    public function assertNotDispatched_can_pass_with_wrong_condition(): void
    {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['BAR']));

        $this->fake_dispatcher->assertNotDispatched(
            'foo_event',
            function ($val) {
                return $val === 'FOO';
            }
        );
    }

    /**
     * @test
     */
    public function assertNotDispatched_will_fail_with_truthful_condition(): void
    {
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['BAR']));

        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched and the condition passed.',
            function () {
                $this->fake_dispatcher->assertNotDispatched(
                    'foo_event',
                    function ($val) {
                        return $val === 'BAR';
                    }
                );
            }
        );
    }

    /**
     * @test
     */
    public function assertNotDispatched_can_pass_with_typehinted_closure_only(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAZ'));

        $this->fake_dispatcher->assertNotDispatched(
            function (EventStub $event_stub) {
                return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAR';
            }
        );
    }

    /**
     * @test
     */
    public function assertDispatched_accepts_a_generic_object_as_the_closure_argument(): void
    {
        $event = new stdClass();
        $event->foo = 'FOO';
        $event->bar = 'BAR';

        $this->fake_dispatcher->dispatch($event);

        $this->fake_dispatcher->assertDispatched(
            function (stdClass $event) {
                return $event->foo === 'FOO' && $event->bar === 'BAR';
            }
        );
    }

    /**
     * @test
     */
    public function assertNotDispatched_will_fail_if_event_object_was_dispatched_and_condition_passed(): void
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAZ'));

        $this->assertFailsWithMessageStarting(
            sprintf('The event [%s] was dispatched and the condition passed.', EventStub::class),
            fn() => $this->fake_dispatcher->assertNotDispatched(
                function (EventStub $event_stub) {
                    return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAZ';
                }
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetListenersResponses();
        $dispatcher = new BaseEventDispatcher(new NewableListenerFactory());
        $this->fake_dispatcher = new TestableEventDispatcher($dispatcher);
    }

    protected function tearDown(): void
    {
        $this->resetListenersResponses();
        parent::tearDown();
    }

}