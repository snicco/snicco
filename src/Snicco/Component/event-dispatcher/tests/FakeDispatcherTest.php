<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\EventDispatcher\Tests\fixtures\EventStub;
use Snicco\Component\EventDispatcher\Dispatcher\FakeDispatcher;
use Snicco\Component\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\Implementations\GenericEvent;
use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;
use Snicco\Component\EventDispatcher\Implementations\ParameterBasedListenerFactory;

final class FakeDispatcherTest extends TestCase
{
    
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
    public function the_fake_dispatcher_proxies_to_the_real_dispatcher_by_default()
    {
        $this->fake_dispatcher->listen('foo_event', function ($val) {
            $this->respondedToEvent('foo_event', 'closure1', $val);
        });
        
        $this->fake_dispatcher->dispatch('foo_event', 'FOOBAR');
        $this->assertListenerRun('foo_event', 'closure1', 'FOOBAR');
    }
    
    /** @test */
    public function faked_events_will_not_be_proxied()
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
    public function the_return_type_is_correct_for_string_events()
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
    public function events_that_are_not_faked_explictly_are_passed_to_the_real_dispatcher()
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
    public function multiple_events_can_be_faked()
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
    public function wildcard_events_can_be_faked()
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
    
}

