<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests;

use stdClass;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\EventDispatcher\GenericEvent;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\TestableEventDispatcher;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\EventStub;
use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;
use Snicco\Component\EventDispatcher\ListenerFactory\NewableListenerFactory;

final class TestableDispatcherTest extends TestCase
{
    
    use AssertListenerResponse;
    
    private TestableEventDispatcher $fake_dispatcher;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->resetListenersResponses();
        $dispatcher = new BaseEventDispatcher(new NewableListenerFactory());
        $this->fake_dispatcher = new TestableEventDispatcher($dispatcher);
    }
    
    protected function tearDown() :void
    {
        $this->resetListenersResponses();
        parent::tearDown();
    }
    
    /** @test */
    public function the_fake_dispatcher_proxies_to_the_real_dispatcher_by_default()
    {
        $this->fake_dispatcher->listen(stdClass::class, function (stdClass $event) {
            $this->respondedToEvent(stdClass::class, 'closure1', $event->val);
        });
        
        $event = new stdClass();
        $event->val = 'FOO';
        
        $res = $this->fake_dispatcher->dispatch($event);
        $this->assertListenerRun(stdClass::class, 'closure1', 'FOO');
        $this->assertSame($event, $res);
    }
    
    /** @test */
    public function faked_events_will_not_be_proxied()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->fake('foo_event');
        
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOOBAR']));
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
        
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOOBAR']));
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->fake_dispatcher->dispatch(new GenericEvent('bar_event', ['FOOBAR']));
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
        
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOOBAR']));
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->fake_dispatcher->dispatch(new GenericEvent('bar_event', ['FOOBAR']));
        $this->assertListenerNotRun('bar_event', 'closure2');
    }
    
    /** @test */
    public function test_faked_events_count_as_dispatched()
    {
        $this->fake_dispatcher->listen('foo_event', function () { });
        
        $this->fake_dispatcher->fake('foo_event');
        
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event'));
        
        $this->fake_dispatcher->assertDispatched('foo_event');
    }
    
    /** @test */
    public function all_but_some_specified_events_can_be_faked()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->listen('bar_event', function ($val) {
            $this->respondedToEvent('bar_event', 'closure2', $val);
        });
        
        $this->fake_dispatcher->fakeExcept('bar_event');
        
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOOBAR']));
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->fake_dispatcher->dispatch(new GenericEvent('bar_event', ['FOOBAR']));
        $this->assertListenerRun('bar_event', 'closure2', 'FOOBAR');
    }
    
    /** @test */
    public function fake_except_throws_exception_for_empty_array()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cant be an empty array');
        
        $this->fake_dispatcher->fakeExcept([]);
    }
    
    /** @test */
    public function the_return_type_is_correct_for_generic_object_events_if_events_are_faked()
    {
        $this->fake_dispatcher->listen(stdClass::class, function (stdClass $event) {
            $this->respondedToEvent(stdClass::class, 'closure1', $event->val);
        });
        
        $this->fake_dispatcher->fake(stdClass::class);
        
        $event = new stdClass();
        $event->val = 'FOO';
        
        $res = $this->fake_dispatcher->dispatch($event);
        $this->fake_dispatcher->assertDispatched(stdClass::class);
        $this->assertListenerNotRun(stdClass::class, 'closure1');
        $this->assertSame($res, $event);
    }
    
    /** @test */
    public function the_return_type_is_correct_for_object_events()
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
    public function events_that_are_not_faked_explicitly_are_passed_to_the_real_dispatcher()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->listen('bar_event', function ($val) {
            $this->respondedToEvent('bar_event', 'closure2', $val);
        });
        
        $this->fake_dispatcher->fake('foo_event');
        
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOOBAR']));
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->resetListenersResponses();
        
        $this->fake_dispatcher->dispatch(new GenericEvent('bar_event', ['FOOBAR']));
        $this->assertListenerRun('bar_event', 'closure2', 'FOOBAR');
    }
    
    /** @test */
    public function multiple_events_can_be_faked()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        $this->fake_dispatcher->listen('bar_event', function ($val) {
            $this->respondedToEvent('bar_event', 'closure2', $val);
        });
        
        $this->fake_dispatcher->fake(['foo_event', 'bar_event']);
        
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event'));
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->resetListenersResponses();
        
        $this->fake_dispatcher->dispatch(new GenericEvent('bar_event'));
        $this->assertListenerNotRun('bar_event', 'closure2');
    }
    
    /** @test */
    public function the_fake_dispatcher_can_be_reset()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->fake('foo_event');
        
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOOBAR']));
        
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $this->resetListenersResponses();
        
        $this->fake_dispatcher->reset();
        
        $this->fake_dispatcher->dispatch(new GenericEvent('foo_event', ['FOOBAR']));
        $this->assertListenerRun('foo_event', 'closure1', 'FOOBAR');
    }
    
}

