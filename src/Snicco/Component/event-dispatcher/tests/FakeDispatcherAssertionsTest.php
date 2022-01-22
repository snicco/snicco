<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\EventDispatcher\Tests\fixtures\EventStub;
use Snicco\Component\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\Component\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\Tests\fixtures\AssertPHPUnitFailures;
use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;
use Snicco\Component\EventDispatcher\Implementations\ParameterBasedListenerFactory;

use function sprintf;

final class FakeDispatcherAssertionsTest extends TestCase
{
    
    use AssertPHPUnitFailures;
    use AssertListenerResponse;
    
    private FakeDispatcher $fake_dispatcher;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->resetListenersResponses();
        $dispatcher = new EventDispatcher(new ParameterBasedListenerFactory());
        $this->fake_dispatcher = new FakeDispatcher($dispatcher);
    }
    
    protected function tearDown() :void
    {
        $this->resetListenersResponses();
        parent::tearDown();
    }
    
    /** @test */
    public function assertNothingDispatched_can_pass()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->assertNotingDispatched();
    }
    
    /** @test */
    public function assertNothingDispatched_can_fail()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOO');
        
        $this->assertFailsWithMessageStarting(
            '1 event[s] dispatched',
            fn() => $this->fake_dispatcher->assertNotingDispatched()
        );
    }
    
    /** @test */
    public function assertDispatched_can_pass()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->dispatch('foo_event', 'FOO');
        
        $this->fake_dispatcher->assertDispatched('foo_event');
    }
    
    /** @test */
    public function assertDispatched_can_fail()
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
    
    /** @test */
    public function assertDispatched_can_pass_with_truthful_condition_about_the_event()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->dispatch('foo_event', 'FOO', 'BAR');
        
        $this->fake_dispatcher->assertDispatched('foo_event', function ($foo, $bar, $event_name) {
            return $foo === 'FOO' && $bar === 'BAR' && $event_name === 'foo_event';
        });
    }
    
    /** @test */
    public function assertDispatched_can_fail_when_the_event_was_not_dispatched()
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
    
    /** @test */
    public function assertDispatched_can_fail_when_the_event_was_dispatched_but_the_additional_condition_didnt_match()
    {
        $this->fake_dispatcher->dispatch('foo_event', 'FOO', 'BAZ');
        
        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched but the provided condition did not pass.',
            function () {
                $this->fake_dispatcher->assertDispatched('foo_event', function ($foo, $bar) {
                    return $foo === 'FOO' && $bar === 'BAR';
                });
            }
        );
    }
    
    /** @test */
    public function assertDispatched_can_pass_with_object_event()
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));
        
        $this->fake_dispatcher->assertDispatched(
            EventStub::class,
            function (EventStub $event_stub) {
                return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAR'
                       && $event_stub->getName()
                          === EventStub::class;
            }
        );
    }
    
    /** @test */
    public function assertDispatched_can_fail_object_event()
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
    
    /** @test */
    public function assertDispatched_can_pass_with_typehinted_closure_only()
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));
        
        $this->fake_dispatcher->assertDispatched(
            function (EventStub $event_stub, string $event_name) {
                return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAR'
                       && $event_name
                          === EventStub::class;
            }
        );
    }
    
    /** @test */
    public function assertDispatched_can_fail_with_typehinted_closure_only()
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));
        
        $this->assertFailsWithMessageStarting(
            sprintf(
                'The event [%s] was dispatched but the provided condition did not pass',
                EventStub::class
            ),
            function () {
                $this->fake_dispatcher->assertDispatched(
                    function (EventStub $event_stub, string $event_name) {
                        return $event_stub->val1 === 'FOO'
                               && $event_stub->val2 === 'BAZ'
                               && $event_name === EventStub::class;
                    }
                );
            },
        );
    }
    
    /** @test */
    public function assertDispatchedTimes_can_pass()
    {
        $this->fake_dispatcher->dispatch('foo_event', 'FOO');
        $this->fake_dispatcher->dispatch('foo_event', 'FOO');
        $this->fake_dispatcher->assertDispatchedTimes('foo_event', 2);
    }
    
    /** @test */
    public function assertDispatchedTimes_can_fail()
    {
        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched [3] time[s].',
            function () {
                $this->fake_dispatcher->dispatch('foo_event', 'FOO');
                $this->fake_dispatcher->dispatch('foo_event', 'FOO');
                $this->fake_dispatcher->dispatch('foo_event', 'FOO');
                $this->fake_dispatcher->assertDispatchedTimes('foo_event', 2);
            }
        );
    }
    
    /** @test */
    public function assertNotDispatched_can_pass()
    {
        $this->fake_dispatcher->assertNotDispatched('foo_event');
    }
    
    /** @test */
    public function assertNotDispatched_can_fail()
    {
        $this->fake_dispatcher->dispatch('foo_event');
        
        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched [1] time[s].',
            function () {
                $this->fake_dispatcher->assertNotDispatched('foo_event');
            }
        );
    }
    
    /** @test */
    public function assertNotDispatched_can_pass_with_wrong_condition()
    {
        $this->fake_dispatcher->dispatch('foo_event', 'BAR');
        
        $this->fake_dispatcher->assertNotDispatched(
            'foo_event',
            function ($val, $event_name) {
                return $val === 'FOO' && $event_name === 'foo_event';
            }
        );
    }
    
    /** @test */
    public function assertNotDispatched_will_fail_with_truthful_condition()
    {
        $this->fake_dispatcher->dispatch('foo_event', 'BAR');
        
        $this->assertFailsWithMessageStarting(
            'The event [foo_event] was dispatched and the condition passed.',
            function () {
                $this->fake_dispatcher->assertNotDispatched(
                    'foo_event',
                    function ($val, $event_name) {
                        return $val === 'BAR' && $event_name === 'foo_event';
                    }
                );
            }
        );
    }
    
    /** @test */
    public function assertNotDispatched_can_pass_with_typehinted_closure_only()
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAZ'));
        
        $this->fake_dispatcher->assertNotDispatched(
            function (EventStub $event_stub) {
                return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAR';
            }
        );
    }
    
    /** @test */
    public function assertNotDispatched_will_fail_if_event_object_was_dispatched_and_condition_passed()
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
    
    /** @test */
    public function assertDispatched_can_pass_with_wildcard_event_and_condition()
    {
        $this->fake_dispatcher->dispatch('user.created', 'calvin');
        
        $this->fake_dispatcher->assertDispatched(
            'user.created',
            function ($user_name, $event_name) {
                return $user_name === 'calvin' && $event_name === 'user.created';
            }
        );
    }
    
    /** @test */
    public function assertDispatched_can_fail_with_wildcard_event_and_condition()
    {
        $this->fake_dispatcher->dispatch('user.created', 'jon doe');
        
        $this->assertFailsWithMessageStarting(
            'The event [user.created] was dispatched but the provided condition did not pass.',
            function () {
                $this->fake_dispatcher->assertDispatched(
                    'user.created',
                    function ($name, $event_name) {
                        return $name === 'calvin'
                               && $event_name
                                  === 'user.created';
                    }
                );
            }
        );
    }
    
}