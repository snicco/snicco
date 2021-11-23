<?php

declare(strict_types=1);

namespace Tests\integration\EventDispatcher;

use stdClass;
use InvalidArgumentException;
use Codeception\TestCase\WPTestCase;
use Tests\concerns\AssertListenerResponse;
use Snicco\Application\IlluminateContainerAdapter;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Snicco\EventDispatcher\Exceptions\InvalidListenerException;
use Snicco\EventDispatcher\Exceptions\UnremovableListenerException;
use Snicco\EventDispatcher\Implementations\ParameterBasedListenerFactory;

class EventDispatcherTest extends WPTestCase
{
    
    use AssertListenerResponse;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->resetListenersResponses();
    }
    
    /** @test */
    public function listeners_are_run_for_matching_events()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('foo_event', function () {
            $this->respondedToEvent('foo_event', 'closure1', 'bar');
        });
        
        $dispatcher->dispatch('foo_event');
        
        $this->assertListenerRun('foo_event', 'closure1', 'bar');
    }
    
    /** @test */
    public function listeners_dont_respond_to_different_events()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('foo_event', function () {
            $this->respondedToEvent('foo_event', 'closure1', 'bar');
        });
        
        $dispatcher->dispatch('bar_event');
        
        $this->assertListenerNotRun('bar_event', 'closure1');
    }
    
    /** @test */
    public function multiple_listeners_can_be_added_for_the_same_event()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('foo_event', function () {
            $this->respondedToEvent('foo_event', 'closure1', 'bar');
        });
        
        $dispatcher->listen('foo_event', function () {
            $this->respondedToEvent('foo_event', 'closure2', 'baz');
        });
        
        $dispatcher->dispatch('foo_event');
        
        $this->assertListenerRun('foo_event', 'closure1', 'bar');
        $this->assertListenerRun('foo_event', 'closure2', 'baz');
    }
    
    /** @test */
    public function arguments_are_passed_to_the_listener()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('foo_event', function ($foo, $bar) {
            $this->respondedToEvent('foo_event', 'closure1', $foo.$bar);
        });
        
        $dispatcher->dispatch('foo_event', 'FOO', 'BAR');
        
        $this->assertListenerRun('foo_event', 'closure1', 'FOOBAR');
    }
    
    /** @test */
    public function events_can_be_objects()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(fixtures\FooEvent::class, function (fixtures\FooEvent $event) {
            $this->respondedToEvent('foo_event', 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new fixtures\FooEvent('foobar'));
        
        $this->assertListenerRun('foo_event', 'closure1', 'foobar');
    }
    
    /** @test */
    public function the_payload_arguments_is_discarded_if_an_object_is_dispatched()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(fixtures\FooEvent::class, function (fixtures\FooEvent $event) {
            $this->respondedToEvent('foo_event', 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new fixtures\FooEvent('foobar'), ['bar', 'baz']);
        
        $this->assertListenerRun('foo_event', 'closure1', 'foobar');
    }
    
    /** @test */
    public function listeners_can_be_registered_as_only_a_class_name_where_the_handle_event_method_will_be_used()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(fixtures\FooEvent::class, fixtures\ClassListener::class);
        
        $dispatcher->dispatch(new fixtures\FooEvent('FOOBAR'));
        
        $this->assertListenerRun(fixtures\FooEvent::class, fixtures\ClassListener::class, 'FOOBAR');
    }
    
    /** @test */
    public function a_listener_can_have_a_custom_method()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            fixtures\FooEvent::class,
            [fixtures\ClassListener::class, 'customHandleMethod']
        );
        
        $dispatcher->dispatch(new fixtures\FooEvent('FOOBAR'));
        
        $this->assertListenerRun(fixtures\FooEvent::class, fixtures\ClassListener::class, 'FOOBAR');
    }
    
    /** @test */
    public function test_non_existing_class_exception()
    {
        $dispatcher = $this->getDispatcher();
        
        $this->expectException(InvalidListenerException::class);
        $this->expectExceptionMessage('The listener [BogusClass] is not a valid class.');
        
        $dispatcher->listen('foo_event', 'BogusClass');
    }
    
    /** @test */
    public function test_missing_handle_method_exception()
    {
        $dispatcher = $this->getDispatcher();
        
        $this->expectException(InvalidListenerException::class);
        $this->expectExceptionMessage(
            "The listener [Tests\integration\EventDispatcher\\fixtures\ListenerWithNoHandleMethod] does not have a handle method and isn't invokable with __invoke().",
        );
        
        $dispatcher->listen('foo_event', fixtures\ListenerWithNoHandleMethod::class);
    }
    
    /** @test */
    public function test_invokable_string_listener_is_allowed_without_handle_method()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('foo_event', fixtures\InvokableListener::class);
        
        $dispatcher->dispatch('foo_event', 'foo', 'bar');
        
        $this->assertListenerRun('foo_event', fixtures\InvokableListener::class, 'foobar');
    }
    
    /** @test */
    public function test_invalid_array_listener_method_exception()
    {
        $dispatcher = $this->getDispatcher();
        
        $this->expectException(InvalidListenerException::class);
        $this->expectExceptionMessage(
            "The listener [Tests\integration\EventDispatcher\\fixtures\ClassListener] does not have a handle method and isn't invokable with __invoke()."
        );
        
        $dispatcher->listen('foo_event', [fixtures\ClassListener::class, 'bogus']);
    }
    
    /** @test */
    public function test_invalid_array_listener_class_exception()
    {
        $dispatcher = $this->getDispatcher();
        
        $this->expectException(InvalidListenerException::class);
        $this->expectExceptionMessage(
            'The listener [BogusClass] is not a valid class.'
        );
        
        $dispatcher->listen('foo_event', ['BogusClass', 'bogus']);
    }
    
    /** @test */
    public function closure_listeners_can_be_created_without_the_event_parameter()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(function (fixtures\FooEvent $event) {
            $this->respondedToEvent(fixtures\FooEvent::class, 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new fixtures\FooEvent('foobar'));
        
        $this->assertListenerRun(fixtures\FooEvent::class, 'closure1', 'foobar');
    }
    
    /** @test */
    public function class_listeners_can_be_removed()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(fixtures\FooEvent::class, fixtures\ClassListener::class);
        
        $dispatcher->dispatch(new fixtures\FooEvent('FOOBAR'));
        $this->assertListenerRun(fixtures\FooEvent::class, fixtures\ClassListener::class, 'FOOBAR');
        
        $this->resetListenersResponses();
        
        $dispatcher->remove(fixtures\FooEvent::class, fixtures\ClassListener::class);
        $dispatcher->dispatch(new fixtures\FooEvent('FOOBAR'));
        $this->assertListenerNotRun(fixtures\FooEvent::class, fixtures\ClassListener::class);
    }
    
    /** @test */
    public function all_listeners_for_an_event_can_be_removed()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(fixtures\FooEvent::class, fixtures\ClassListener::class);
        $dispatcher->listen(fixtures\FooEvent::class, fixtures\ClassListener2::class);
        
        $dispatcher->dispatch(new fixtures\FooEvent('FOOBAR'));
        $this->assertListenerRun(fixtures\FooEvent::class, fixtures\ClassListener::class, 'FOOBAR');
        $this->assertListenerRun(
            fixtures\FooEvent::class,
            fixtures\ClassListener2::class,
            'FOOBAR'
        );
        
        $this->resetListenersResponses();
        
        $dispatcher->remove(fixtures\FooEvent::class);
        $dispatcher->dispatch(new fixtures\FooEvent('FOOBAR'));
        $this->assertListenerNotRun(fixtures\FooEvent::class, fixtures\ClassListener::class);
        $this->assertListenerNotRun(fixtures\FooEvent::class, fixtures\ClassListener2::class);
    }
    
    /** @test */
    public function a_class_listener_can_be_marked_as_unremovable()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(fixtures\FooEvent::class, fixtures\ClassListener::class, false);
        
        $dispatcher->dispatch(new fixtures\FooEvent('FOOBAR'));
        
        $this->expectException(UnremovableListenerException::class);
        
        $dispatcher->remove(fixtures\FooEvent::class, fixtures\ClassListener::class);
    }
    
    /** @test */
    public function event_objects_are_immutable()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(fixtures\FooEvent::class, function (fixtures\FooEvent $event) {
            $val = $event->val;
            $event->val = 'FOOBAZ';
            $this->respondedToEvent(fixtures\FooEvent::class, 'closure1', $val);
        });
        $dispatcher->listen(fixtures\FooEvent::class, function (fixtures\FooEvent $event) {
            $this->respondedToEvent(fixtures\FooEvent::class, 'closure2', $event->val);
        });
        
        $dispatcher->dispatch(new fixtures\FooEvent('FOOBAR'));
        
        $this->assertListenerRun(fixtures\FooEvent::class, 'closure1', 'FOOBAR');
        $this->assertListenerRun(fixtures\FooEvent::class, 'closure2', 'FOOBAR');
    }
    
    /** @test */
    public function event_objects_can_be_mutable()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(fixtures\MutableEvent::class, function (fixtures\MutableEvent $event) {
            $val = $event->val;
            $event->val = 'FOOBAZ';
            $this->respondedToEvent(fixtures\MutableEvent::class, 'closure1', $val);
        });
        $dispatcher->listen(fixtures\MutableEvent::class, function (fixtures\MutableEvent $event) {
            $this->respondedToEvent(fixtures\MutableEvent::class, 'closure2', $event->val);
        });
        
        $dispatcher->dispatch(new fixtures\MutableEvent('FOOBAR'));
        
        $this->assertListenerRun(fixtures\MutableEvent::class, 'closure1', 'FOOBAR');
        $this->assertListenerRun(fixtures\MutableEvent::class, 'closure2', 'FOOBAZ');
    }
    
    /** @test */
    public function events_can_be_filterable_and_the_final_value_will_be_returned()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            fixtures\FilterableEvent::class,
            function (fixtures\FilterableEvent $event) {
                $event->val = $event->val.':Filter1:';
            }
        );
        $dispatcher->listen(
            fixtures\FilterableEvent::class,
            function (fixtures\FilterableEvent $event) {
                $event->val = $event->val.'Filter2';
            }
        );
        
        $result = $dispatcher->dispatch($event = new fixtures\FilterableEvent('FOOBAR'));
        
        $this->assertInstanceOf(fixtures\FilterableEvent::class, $result);
        $this->assertSame('FOOBAR:Filter1:Filter2', $result->val);
    }
    
    /** @test */
    public function test_dispatches_conditionally_can_prevent_the_event_dispatching()
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->listen(function (fixtures\GreaterThenThree $event) {
            $this->respondedToEvent(fixtures\GreaterThenThree::class, 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new fixtures\GreaterThenThree(3));
        
        $this->assertListenerNotRun(fixtures\GreaterThenThree::class, 'closure1');
    }
    
    /** @test */
    public function test_dispatches_conditionally_dispatching_works_if_passing()
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->listen(function (fixtures\GreaterThenThree $event) {
            $this->respondedToEvent(fixtures\GreaterThenThree::class, 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new fixtures\GreaterThenThree(4));
        
        $this->assertListenerRun(fixtures\GreaterThenThree::class, 'closure1', 4);
    }
    
    /** @test */
    public function listeners_can_listen_to_interfaces()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(function (fixtures\LoggableEvent $event) {
            $this->respondedToEvent($event, 'closure1', $event->message());
        });
        
        $dispatcher->dispatch(new fixtures\LogEvent1('foobar'));
        $this->assertListenerRun(fixtures\LogEvent1::class, 'closure1', 'foobar');
        
        $this->resetListenersResponses();
        
        $dispatcher->dispatch(new fixtures\LogEvent2('foobar'));
        $this->assertListenerRun(fixtures\LogEvent2::class, 'closure1', 'foobar');
    }
    
    /** @test */
    public function listeners_can_listen_to_abstract_classes_up_to_one_parent_level()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(function (fixtures\AbstractLogin $event) {
            $this->respondedToEvent($event, 'closure1', $event->message());
        });
        
        $dispatcher->dispatch(new fixtures\PasswordLogin());
        $this->assertListenerRun(fixtures\PasswordLogin::class, 'closure1', 'password login');
    }
    
    /** @test */
    public function a_wildcard_listener_can_be_created_and_receive_the_event_name_as_the_first_parameter()
    {
        $dispatcher = $this->getDispatcher();
        
        $user = new stdClass();
        $user->first_name = 'calvin';
        
        $dispatcher->listen('user.*', function ($event_name, $passed_user, $time) use ($user) {
            $this->assertSame($user, $passed_user);
            $this->respondedToEvent($event_name, 'closure1', $time);
        });
        
        $dispatcher->dispatch('user.created', $user, 1234);
        $this->assertListenerRun('user.created', 'closure1', 1234);
        
        $this->resetListenersResponses();
        
        $dispatcher->dispatch('user.deleted', $user, 5678);
        $this->assertListenerRun('user.deleted', 'closure1', 5678);
    }
    
    /** @test */
    public function test_invalid_argument_for_dispatching()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A dispatched event has to be a string or an instance of [Snicco\EventDispatcher\Contracts\Event].'
        );
        
        $this->getDispatcher()->dispatch(new stdClass(), []);
    }
    
    /** @test */
    public function an_event_can_be_dispatched_with_a_custom_name()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('my_plugin_user_created', function (fixtures\UserCreated $event) {
            $this->respondedToEvent($event->getName(), 'closure1', $event->user_name);
        });
        
        $dispatcher->dispatch(new fixtures\UserCreated('calvin'));
        
        $this->assertListenerRun('my_plugin_user_created', 'closure1', 'calvin');
    }
    
    private function getDispatcher($container = null) :EventDispatcher
    {
        return new EventDispatcher(
            new ParameterBasedListenerFactory($container ?? new IlluminateContainerAdapter())
        );
    }
    
}
