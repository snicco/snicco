<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\integration;

use stdClass;
use InvalidArgumentException;
use Codeception\TestCase\WPTestCase;
use Tests\BetterWPHooks\fixtures\FooEvent;
use Tests\BetterWPHooks\fixtures\BarEvent;
use Tests\BetterWPHooks\fixtures\LogEvent1;
use Tests\BetterWPHooks\fixtures\LogEvent2;
use Tests\BetterWPHooks\fixtures\UserCreated;
use Tests\BetterWPHooks\fixtures\MutableEvent;
use Tests\BetterWPHooks\fixtures\ClassListener;
use Tests\BetterWPHooks\fixtures\LoggableEvent;
use Tests\BetterWPHooks\fixtures\AbstractLogin;
use Tests\BetterWPHooks\fixtures\PasswordLogin;
use Tests\BetterWPHooks\fixtures\ClassListener2;
use Tests\BetterWPHooks\fixtures\FilterableEvent;
use Tests\BetterWPHooks\fixtures\GreaterThenThree;
use Tests\BetterWPHooks\fixtures\WildcardListener;
use Tests\BetterWPHooks\fixtures\InvokableListener;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Tests\BetterWPHooks\helpers\AssertListenerResponse;
use Tests\BetterWPHooks\fixtures\ListenerWithNoHandleMethod;
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
    public function events_can_be_instances_of_the_event_interface()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, function (FooEvent $event) {
            $this->respondedToEvent('foo_event', 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new FooEvent('foobar'));
        
        $this->assertListenerRun('foo_event', 'closure1', 'foobar');
    }
    
    /** @test */
    public function payloads_can_be_plain_objects()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('foo_event', function (stdClass $event) {
            $this->respondedToEvent('foo_event', 'closure1', $event->foo.$event->bar);
        });
        
        $payload = new stdClass();
        $payload->foo = 'FOO';
        $payload->bar = 'BAR';
        $dispatcher->dispatch('foo_event', $payload);
        
        $this->assertListenerRun('foo_event', 'closure1', 'FOOBAR');
    }
    
    /** @test */
    public function events_can_be_plain_objects()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(stdClass::class, function (stdClass $event) {
            $this->respondedToEvent(stdClass::class, 'closure1', $event->foo.$event->bar);
        });
        
        $payload = new stdClass();
        $payload->foo = 'FOO';
        $payload->bar = 'BAR';
        $dispatcher->dispatch($payload);
        
        $this->assertListenerRun(stdClass::class, 'closure1', 'FOOBAR');
    }
    
    /** @test */
    public function the_payload_arguments_is_discarded_if_an_object_is_dispatched()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, function (FooEvent $event) {
            $this->respondedToEvent('foo_event', 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new FooEvent('foobar'), ['bar', 'baz']);
        
        $this->assertListenerRun('foo_event', 'closure1', 'foobar');
    }
    
    /** @test */
    public function listeners_can_be_registered_as_only_a_class_name_where_the_handle_event_method_will_be_used()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, ClassListener::class);
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        
        $this->assertListenerRun(FooEvent::class, ClassListener::class, 'FOOBAR');
    }
    
    /** @test */
    public function a_listener_can_have_a_custom_method()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            FooEvent::class,
            [ClassListener::class, 'customHandleMethod']
        );
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        
        $this->assertListenerRun(FooEvent::class, ClassListener::class, 'FOOBAR');
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
            sprintf(
                "The listener [%s] does not have a handle method and is not invokable with __invoke().",
                ListenerWithNoHandleMethod::class
            ),
        );
        
        $dispatcher->listen('foo_event', ListenerWithNoHandleMethod::class);
    }
    
    /** @test */
    public function test_invokable_string_listener_is_allowed_without_handle_method()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('foo_event', InvokableListener::class);
        
        $dispatcher->dispatch('foo_event', 'foo', 'bar');
        
        $this->assertListenerRun('foo_event', InvokableListener::class, 'foobar');
    }
    
    /** @test */
    public function test_invalid_array_listener_method_exception()
    {
        $dispatcher = $this->getDispatcher();
        
        $this->expectException(InvalidListenerException::class);
        $this->expectExceptionMessage(
            sprintf(
                "The listener [%s] does not have a handle method and is not invokable with __invoke().",
                ClassListener::class
            )
        );
        
        $dispatcher->listen('foo_event', [ClassListener::class, 'bogus']);
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
        
        $dispatcher->listen(function (FooEvent $event) {
            $this->respondedToEvent(FooEvent::class, 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new FooEvent('foobar'));
        
        $this->assertListenerRun(FooEvent::class, 'closure1', 'foobar');
    }
    
    /** @test */
    public function class_listeners_can_be_removed()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, ClassListener::class);
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        $this->assertListenerRun(FooEvent::class, ClassListener::class, 'FOOBAR');
        
        $this->resetListenersResponses();
        
        $dispatcher->remove(FooEvent::class, ClassListener::class);
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        $this->assertListenerNotRun(FooEvent::class, ClassListener::class);
    }
    
    /** @test */
    public function closure_listeners_can_be_removed_by_object_id()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            FooEvent::class,
            $closure = function (FooEvent $event) {
                $this->respondedToEvent(FooEvent::class, 'closure1', $event->val);
            }
        );
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        $this->assertListenerRun(FooEvent::class, 'closure1', 'FOOBAR');
        
        $this->resetListenersResponses();
        
        $dispatcher->remove(FooEvent::class, $closure);
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        $this->assertListenerNotRun(FooEvent::class, 'closure1');
    }
    
    /** @test */
    public function class_listeners_can_be_removed_by_method()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, [ClassListener::class, 'customHandleMethod']);
        $dispatcher->listen(BarEvent::class, [ClassListener::class, 'customHandleMethod']);
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        $this->assertListenerRun(FooEvent::class, ClassListener::class, 'FOOBAR');
        
        $this->resetListenersResponses();
        
        $dispatcher->remove(FooEvent::class, [ClassListener::class, 'customHandleMethod']);
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        $this->assertListenerNotRun(FooEvent::class, ClassListener::class);
        
        // Other methods are still registered as listeners.
        $dispatcher->dispatch(new BarEvent('BARBAZ'));
        $this->assertListenerRun(BarEvent::class, ClassListener::class, 'BARBAZ');
    }
    
    /** @test */
    public function all_listeners_for_an_event_can_be_removed()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, ClassListener::class);
        $dispatcher->listen(FooEvent::class, ClassListener2::class);
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        $this->assertListenerRun(FooEvent::class, ClassListener::class, 'FOOBAR');
        $this->assertListenerRun(
            FooEvent::class,
            ClassListener2::class,
            'FOOBAR'
        );
        
        $this->resetListenersResponses();
        
        $dispatcher->remove(FooEvent::class);
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        $this->assertListenerNotRun(FooEvent::class, ClassListener::class);
        $this->assertListenerNotRun(FooEvent::class, ClassListener2::class);
    }
    
    /** @test */
    public function a_class_listener_can_be_marked_as_unremovable()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, ClassListener::class, false);
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        
        $this->expectException(UnremovableListenerException::class);
        
        $dispatcher->remove(FooEvent::class, ClassListener::class);
    }
    
    /** @test */
    public function event_objects_are_immutable()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, function (FooEvent $event) {
            $val = $event->val;
            $event->val = 'FOOBAZ';
            $this->respondedToEvent(FooEvent::class, 'closure1', $val);
        });
        $dispatcher->listen(FooEvent::class, function (FooEvent $event) {
            $this->respondedToEvent(FooEvent::class, 'closure2', $event->val);
        });
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        
        $this->assertListenerRun(FooEvent::class, 'closure1', 'FOOBAR');
        $this->assertListenerRun(FooEvent::class, 'closure2', 'FOOBAR');
    }
    
    /** @test */
    public function event_objects_can_be_mutable()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(MutableEvent::class, function (MutableEvent $event) {
            $val = $event->val;
            $event->val = 'FOOBAZ';
            $this->respondedToEvent(MutableEvent::class, 'closure1', $val);
        });
        $dispatcher->listen(MutableEvent::class, function (MutableEvent $event) {
            $this->respondedToEvent(MutableEvent::class, 'closure2', $event->val);
        });
        
        $dispatcher->dispatch(new MutableEvent('FOOBAR'));
        
        $this->assertListenerRun(MutableEvent::class, 'closure1', 'FOOBAR');
        $this->assertListenerRun(MutableEvent::class, 'closure2', 'FOOBAZ');
    }
    
    /** @test */
    public function events_can_be_filterable_and_the_final_value_will_be_returned()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            FilterableEvent::class,
            function (FilterableEvent $event) {
                $event->val = $event->val.':Filter1:';
            }
        );
        $dispatcher->listen(
            FilterableEvent::class,
            function (FilterableEvent $event) {
                $event->val = $event->val.'Filter2';
            }
        );
        
        $result = $dispatcher->dispatch($event = new FilterableEvent('FOOBAR'));
        
        $this->assertInstanceOf(FilterableEvent::class, $result);
        $this->assertSame('FOOBAR:Filter1:Filter2', $result->val);
    }
    
    /** @test */
    public function test_dispatches_conditionally_can_prevent_the_event_dispatching()
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->listen(function (GreaterThenThree $event) {
            $this->respondedToEvent(GreaterThenThree::class, 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new GreaterThenThree(3));
        
        $this->assertListenerNotRun(GreaterThenThree::class, 'closure1');
    }
    
    /** @test */
    public function test_dispatches_conditionally_dispatching_works_if_passing()
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->listen(function (GreaterThenThree $event) {
            $this->respondedToEvent(GreaterThenThree::class, 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new GreaterThenThree(4));
        
        $this->assertListenerRun(GreaterThenThree::class, 'closure1', 4);
    }
    
    /** @test */
    public function listeners_can_listen_to_interfaces()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(function (LoggableEvent $event) {
            $this->respondedToEvent($event, 'closure1', $event->message());
        });
        
        $dispatcher->dispatch(new LogEvent1('foobar'));
        $this->assertListenerRun(LogEvent1::class, 'closure1', 'foobar');
        
        $this->resetListenersResponses();
        
        $dispatcher->dispatch(new LogEvent2('foobar'));
        $this->assertListenerRun(LogEvent2::class, 'closure1', 'foobar');
    }
    
    /** @test */
    public function listeners_can_listen_to_abstract_classes_up_to_one_parent_level()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(function (AbstractLogin $event) {
            $this->respondedToEvent($event, 'closure1', $event->message());
        });
        
        $dispatcher->dispatch(new PasswordLogin());
        $this->assertListenerRun(PasswordLogin::class, 'closure1', 'password login');
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
    public function wildcard_listeners_can_be_classes()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('user.*', [WildcardListener::class, 'customMethod1']);
        
        $dispatcher->dispatch('user.created', 'calvin');
        $this->assertListenerRun('user.created', WildcardListener::class, 'calvin');
    }
    
    /** @test */
    public function test_invalid_event_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A dispatched event has to be a string or an instance of [Snicco\EventDispatcher\Contracts\Event].'
        );
        
        $this->getDispatcher()->dispatch(1, []);
    }
    
    /** @test */
    public function an_event_can_be_dispatched_with_a_custom_name()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('my_plugin_user_created', function (UserCreated $event) {
            $this->respondedToEvent($event->getName(), 'closure1', $event->user_name);
        });
        
        $dispatcher->dispatch(new UserCreated('calvin'));
        
        $this->assertListenerRun('my_plugin_user_created', 'closure1', 'calvin');
    }
    
    /** @test */
    public function an_event_can_be_muted()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            FooEvent::class,
            [ClassListener::class, 'customHandleMethod']
        );
        
        $dispatcher->listen(
            FooEvent::class,
            [ClassListener2::class, 'handle']
        );
        
        $dispatcher->mute(FooEvent::class);
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        
        $this->assertListenerNotRun(FooEvent::class, ClassListener::class);
        $this->assertListenerNotRun(FooEvent::class, ClassListener2::class);
        
        $dispatcher->unmute(FooEvent::class);
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        
        $this->assertListenerRun(FooEvent::class, ClassListener::class, 'FOOBAR');
        $this->assertListenerRun(FooEvent::class, ClassListener2::class, 'FOOBAR');
    }
    
    /** @test */
    public function specific_listeners_can_be_muted()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            FooEvent::class,
            [ClassListener::class, 'customHandleMethod']
        );
        
        $dispatcher->listen(
            FooEvent::class,
            [ClassListener2::class, 'handle']
        );
        
        $dispatcher->mute(FooEvent::class, [ClassListener::class, 'customHandleMethod']);
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        
        $this->assertListenerNotRun(FooEvent::class, ClassListener::class);
        $this->assertListenerRun(FooEvent::class, ClassListener2::class, 'FOOBAR');
        
        $this->resetListenersResponses();
        
        $dispatcher->unmute(FooEvent::class, [ClassListener::class, 'customHandleMethod']);
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        
        $this->assertListenerRun(FooEvent::class, ClassListener::class, 'FOOBAR');
        $this->assertListenerRun(FooEvent::class, ClassListener2::class, 'FOOBAR');
    }
    
    private function getDispatcher() :EventDispatcher
    {
        return new EventDispatcher(
            new ParameterBasedListenerFactory()
        );
    }
    
}
