<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\integration;

use Codeception\TestCase\WPTestCase;
use Tests\BetterWPHooks\fixtures\EventStub;
use PHPUnit\Framework\ExpectationFailedException;
use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Tests\BetterWPHooks\helpers\AssertListenerResponse;
use Snicco\EventDispatcher\Implementations\GenericEvent;
use Tests\Codeception\shared\helpers\AssertPHPUnitFailures;
use Snicco\EventDispatcher\Implementations\ParameterBasedListenerFactory;

final class FakeDispatcherTest extends WPTestCase
{
    
    use AssertListenerResponse;
    use AssertPHPUnitFailures;
    
    private FakeDispatcher $fake_dispatcher;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->resetListenersResponses();
        $dispatcher = new EventDispatcher(new ParameterBasedListenerFactory());
        $this->fake_dispatcher = new FakeDispatcher($dispatcher);
    }
    
    /**
     * BEHAVIOUR
     */
    
    /** @test */
    public function test_fake_is_a_proxy_by_default()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerRun('foo_event', 'closure1', 'FOOBAR');
    }
    
    /** @test */
    public function test_events_can_be_faked_and_wont_be_run()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->fake('foo_event');
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerNotRun('foo_event', 'closure1');
    }
    
    /** @test */
    public function all_events_can_be_faked()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->listen('bar_event', function ($val) {
            $this->respondedToEvent('bar_event', 'closure2', $val);
        });
        
        $this->fake_dispatcher->fake();
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->fake_dispatcher->dispatch('bar_event', 'FOOBAR');
        $this->assertListenerNotRun('bar_event', 'closure2');
    }
    
    /** @test */
    public function all_events_can_be_faked_as_an_alias_call()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->listen('bar_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure2', $val);
        });
        
        $this->fake_dispatcher->fakeAll();
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->fake_dispatcher->dispatch('bar_event', 'FOOBAR');
        $this->assertListenerNotRun('bar_event', 'closure2');
    }
    
    /** @test */
    public function test_faked_events_count_as_dispatched()
    {
        $this->fake_dispatcher->listen('foo_event', function () {
        });
        
        $this->fake_dispatcher->fake('foo_event');
        
        $this->fake_dispatcher->dispatch('foo_event', 'foo');
        
        $this->fake_dispatcher->assertDispatched('foo_event');
    }
    
    /** @test */
    public function test_fake_except()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->listen('bar_event', function ($val) {
            $this->respondedToEvent('bar_event', 'closure2', $val);
        });
        
        $this->fake_dispatcher->fakeExcept('bar_event');
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->fake_dispatcher->dispatch('bar_event', 'FOOBAR');
        $this->assertListenerRun('bar_event', 'closure2', 'FOOBAR');
    }
    
    /** @test */
    public function test_confirms_to_return_type_for_string_events()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->fake('foo_event');
        
        $event = $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        
        $this->assertInstanceOf(GenericEvent::class, $event);
        $this->assertSame(['FOOBAR'], $event->getPayload());
    }
    
    /** @test */
    public function test_confirms_to_return_type_for_object_events()
    {
        $this->fake_dispatcher->listen(EventStub::class, function ($event) {
            $this->respondedToEvent(
                EventStub::class,
                'closure1',
                $event->val1.$event->val2
            );
        });
        $this->fake_dispatcher->fake(EventStub::class);
        
        $result = $this->fake_dispatcher->dispatch($event = new EventStub('FOO', 'BAR'));
        
        $this->assertSame($event, $result);
        $this->assertSame('FOO', $result->val1);
        $this->assertSame('BAR', $result->val2);
        
        $this->assertListenerNotRun(EventStub::class, 'closure1');
    }
    
    /** @test */
    public function test_only_faked_events_get_faked()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->listen('bar_event', function ($val) {
            $this->respondedToEvent('bar_event', 'closure2', $val);
        });
        
        $this->fake_dispatcher->fake('foo_event');
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->resetListenersResponses();
        
        $this->fake_dispatcher->dispatch('bar_event', 'FOOBAR');
        $this->assertListenerRun('bar_event', 'closure2', 'FOOBAR');
    }
    
    /** @test */
    public function test_multiple_events_can_be_faked()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->listen('bar_event', function ($val) {
            $this->respondedToEvent('bar_event', 'closure2', $val);
        });
        
        $this->fake_dispatcher->fake(['foo_event', 'bar_event']);
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->resetListenersResponses();
        
        $this->fake_dispatcher->dispatch('bar_event', 'FOOBAR');
        $this->assertListenerNotRun('bar_event', 'closure2');
    }
    
    /** @test */
    public function test_wildcard_events_can_be_faked()
    {
        $this->fake_dispatcher->listen('user.*', function ($event_name, $user_name) {
            $this->respondedToEvent($event_name, 'closure1', $user_name);
        });
        
        $this->fake_dispatcher->listen('post.*', function ($event_name, $post_name) {
            $this->respondedToEvent($event_name, 'closure2', $post_name);
        });
        
        $this->fake_dispatcher->fake('user.*');
        
        $this->fake_dispatcher->dispatch('user.created', 'calvin');
        $this->assertListenerNotRun('user.created', 'closure1');
        
        $this->fake_dispatcher->dispatch('post.created', 'my_post');
        $this->assertListenerRun('post.created', 'closure2', 'my_post');
        
        $this->resetListenersResponses();
        
        $this->fake_dispatcher->dispatch('post.deleted', 'my_post');
        $this->assertListenerRun('post.deleted', 'closure2', 'my_post');
    }
    
    /** @test */
    public function the_fake_dispatcher_can_be_reset()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->fake('foo_event');
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->resetListenersResponses();
        
        $this->fake_dispatcher->reset();
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerRun('foo_event', 'closure1', 'FOOBAR');
    }
    
    /**
     * ASSERTIONS
     */
    
    /** @test */
    public function testAssertNothingDispatchedCanPass()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->assertNotingDispatched();
    }
    
    /** @test */
    public function testAssertNothingDispatchedCanFail()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOO');
        
        try {
            $this->fake_dispatcher->assertNotingDispatched();
            $this->failBecauseOfWrongAssertion();
        } catch (ExpectationFailedException $e) {
            $this->assertStringStartsWith("1 event[s] dispatched.", $e->getMessage());
        }
    }
    
    /** @test */
    public function testAssertDispatchedCanPass()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->dispatch('foo_event', 'FOO');
        
        $this->fake_dispatcher->assertDispatched('foo_event');
    }
    
    /** @test */
    public function testAssertDispatchedCanFail()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        try {
            $this->fake_dispatcher->assertDispatched('foo_event');
            $this->failBecauseOfWrongAssertion();
        } catch (ExpectationFailedException $e) {
            $this->assertStringStartsWith(
                'The event [foo_event] was not dispatched',
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function testAssertDispatchedCanPassWithCondition()
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
    public function testAssertDispatchedCanFailWithConditionIfNotDispatched()
    {
        $this->assertFailing(function () {
            $this->fake_dispatcher->assertDispatched('foo_event', function ($foo, $bar) {
                return $foo === 'FOO' && $bar === 'BAR';
            });
        }, "The event [foo_event] was not dispatched.");
    }
    
    /** @test */
    public function testAssertDispatchedCanFailIfDispatchedButConditionDoesntMatch()
    {
        $this->fake_dispatcher->dispatch('foo_event', 'FOO', 'BAZ');
        
        $this->assertFailing(function () {
            $this->fake_dispatcher->assertDispatched('foo_event', function ($foo, $bar) {
                return $foo === 'FOO' && $bar === 'BAR';
            });
        }, 'The event [foo_event] was dispatched but the provided condition did not pass.');
    }
    
    /** @test */
    public function testAssertDispatchedCanPassWithObjectEvent()
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
    public function testAssertDispatchedCanFailWithObject()
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));
        
        $this->assertFailing(
            
            function () {
                $this->fake_dispatcher->assertDispatched(
                    EventStub::class,
                    function (EventStub $event_stub) {
                        return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAZ';
                    }
                );
            },
            'The event ['
            .EventStub::class
            .'] was dispatched but the provided condition did not pass.'
        );
    }
    
    /** @test */
    public function testAssertDispatchedCanPassWorksWithClosureOnly()
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
    public function testAssertDispatchedCanFailWithClosureOnly()
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAR'));
        
        $this->assertFailing(
            
            function () {
                $this->fake_dispatcher->assertDispatched(
                    function (EventStub $event_stub, string $event_name) {
                        return $event_stub->val1 === 'FOO'
                               && $event_stub->val2 === 'BAZ'
                               && $event_name === EventStub::class;
                    }
                );
            },
            'The event ['
            .EventStub::class
            .'] was dispatched but the provided condition did not pass.'
        );
    }
    
    /** @test */
    public function testAssertDispatchedTimesCanPass()
    {
        $this->fake_dispatcher->dispatch('foo_event', 'FOO');
        $this->fake_dispatcher->dispatch('foo_event', 'FOO');
        $this->fake_dispatcher->assertDispatchedTimes('foo_event', 2);
    }
    
    /** @test */
    public function testAssertDispatchedTimesCanFail()
    {
        $this->assertFailing(function () {
            $this->fake_dispatcher->dispatch('foo_event', 'FOO');
            $this->fake_dispatcher->dispatch('foo_event', 'FOO');
            $this->fake_dispatcher->dispatch('foo_event', 'FOO');
            $this->fake_dispatcher->assertDispatchedTimes('foo_event', 2);
        }, "The event [foo_event] was dispatched [3] time[s].");
    }
    
    /** @test */
    public function testAssertNotDispatchedCanPass()
    {
        $this->fake_dispatcher->assertNotDispatched('foo_event');
    }
    
    /** @test */
    public function testAssertNotDispatchedCanFail()
    {
        $this->fake_dispatcher->dispatch('foo_event');
        
        $this->assertFailing(function () {
            $this->fake_dispatcher->assertNotDispatched('foo_event');
        }, "The event [foo_event] was dispatched [1] time[s].");
    }
    
    /** @test */
    public function testAssertNotDispatchedCanPassWithCondition()
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
    public function testAssertNotDispatchedCanFailWithCondition()
    {
        $this->fake_dispatcher->dispatch('foo_event', 'BAR');
        
        $this->assertFailing(function () {
            $this->fake_dispatcher->assertNotDispatched(
                'foo_event',
                function ($val, $event_name) {
                    return $val === 'BAR' && $event_name === 'foo_event';
                }
            );
        }, "The event [foo_event] was dispatched and the condition passed.");
    }
    
    /** @test */
    public function testAssertNotDispatchedCanPassWithObjectAndCondition()
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAZ'));
        
        $this->fake_dispatcher->assertNotDispatched(
            function (EventStub $event_stub) {
                return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAR';
            }
        );
    }
    
    /** @test */
    public function testAssertNotDispatchedCanPassWithObjectAndConditionCanFail()
    {
        $this->fake_dispatcher->dispatch(new EventStub('FOO', 'BAZ'));
        
        $this->assertFailing(function () {
            $this->fake_dispatcher->assertNotDispatched(
                function (EventStub $event_stub) {
                    return $event_stub->val1 === 'FOO' && $event_stub->val2 === 'BAZ';
                }
            );
        }, "The event [".EventStub::class."] was dispatched and the condition passed.");
    }
    
    /** @test */
    public function testAssertDispatchedWithWildcardEventAndConditionCanPass()
    {
        $this->fake_dispatcher->dispatch('user.created', 'calvin');
        
        $this->fake_dispatcher->assertDispatched(
            'user.created',
            function ($user_name, $event_name) {
                return $user_name === 'calvin' && $event_name === 'user.created';
            }
        );
    }
    
    /**
     * @test
     */
    public function testAssertDispatchedWithWildcardEventAndConditionCanFail()
    {
        $this->fake_dispatcher->dispatch('user.created', 'jon doe');
        
        $this->assertFailing(function () {
            $this->fake_dispatcher->assertDispatched(
                'user.created',
                function ($name, $event_name) {
                    return $name === 'calvin'
                           && $event_name
                              === 'user.created';
                }
            );
        }, "The event [user.created] was dispatched but the provided condition did not pass.");
    }
    
}

