<?php

declare(strict_types=1);

namespace Tests\integration\EventDispatcher;

use stdClass;
use InvalidArgumentException;
use Codeception\TestCase\WPTestCase;
use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ImmutableEvent;
use Snicco\EventDispatcher\ClassAsPayload;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\EventDispatcher;
use Snicco\EventDispatcher\Contracts\Mutable;
use Snicco\Application\IlluminateContainerAdapter;
use Snicco\EventDispatcher\Contracts\IsForbiddenToWordPress;
use Snicco\EventDispatcher\Contracts\DispatchesConditionally;
use Snicco\EventDispatcher\Exceptions\InvalidListenerException;
use Snicco\EventDispatcher\Exceptions\UnremovableListenerException;
use Snicco\EventDispatcher\Implementations\ParameterBasedListenerFactory;

interface LoggableEvent extends Event
{
    
    public function message();
    
}

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
        
        $dispatcher->listen(FooEvent::class, function (FooEvent $event) {
            $this->respondedToEvent('foo_event', 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new FooEvent('foobar'));
        
        $this->assertListenerRun('foo_event', 'closure1', 'foobar');
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
        
        $dispatcher->listen(FooEvent::class, [ClassListener::class, 'customHandleMethod']);
        
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
            "The listener [Tests\integration\EventDispatcher\ListenerWithNoHandleMethod] does not have a handle method and isn't invokable with __invoke().",
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
            "The listener [Tests\integration\EventDispatcher\ClassListener] does not have a handle method and isn't invokable with __invoke()."
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
    public function all_listeners_for_an_event_can_be_removed()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, ClassListener::class);
        $dispatcher->listen(FooEvent::class, ClassListener2::class);
        
        $dispatcher->dispatch(new FooEvent('FOOBAR'));
        $this->assertListenerRun(FooEvent::class, ClassListener::class, 'FOOBAR');
        $this->assertListenerRun(FooEvent::class, ClassListener2::class, 'FOOBAR');
        
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
        
        $dispatcher->listen(FilterableEvent::class, function (FilterableEvent $event) {
            $event->val = $event->val.':Filter1:';
        });
        $dispatcher->listen(FilterableEvent::class, function (FilterableEvent $event) {
            $event->val = $event->val.'Filter2';
        });
        
        $result = $dispatcher->dispatch($event = new FilterableEvent('FOOBAR'));
        
        $this->assertInstanceOf(FilterableEvent::class, $result);
        $this->assertSame('FOOBAR:Filter1:Filter2', $result->val);
    }
    
    /** @test */
    public function if_events_are_filterable_users_can_hook_into_them_using_add_filter()
    {
        $count = 0;
        add_filter(FilterableEvent::class, function (FilterableEvent $event) use (&$count) {
            $count++;
            $event->val = 'filtered';
        });
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FilterableEvent::class, function (FilterableEvent $event) {
            $event->val = $event->val.':Filter1:';
        });
        $dispatcher->listen(FilterableEvent::class, function (FilterableEvent $event) {
            $event->val = $event->val.'Filter2';
        });
        
        $result = $dispatcher->dispatch(new FilterableEvent('FOOBAR'));
        
        $this->assertInstanceOf(FilterableEvent::class, $result);
        $this->assertSame('filtered', $result->val);
        
        $this->assertSame(1, $count, 'WordPress filter not called.');
    }
    
    /** @test */
    public function wordpress_filters_can_be_used_if_no_dispatcher_filters_are_registered()
    {
        $count = 0;
        add_filter(FilterableEvent::class, function (FilterableEvent $event) use (&$count) {
            $count++;
            $event->val = $event->val.':filtered';
        });
        
        $dispatcher = $this->getDispatcher();
        
        $result = $dispatcher->dispatch(new FilterableEvent('FOOBAR'));
        
        $this->assertInstanceOf(FilterableEvent::class, $result);
        $this->assertSame('FOOBAR:filtered', $result->val);
        
        $this->assertSame(1, $count, 'WordPress filter not called.');
    }
    
    /** @test */
    public function events_can_be_marked_as_forbidden_to_wordpress()
    {
        $count = 0;
        add_filter(
            ForbiddenToWordPressEvent::class,
            function (ForbiddenToWordPressEvent $event) use (&$count) {
                $count++;
                $event->val = $event->val.':filtered_by_wordpress';
            }
        );
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(function (ForbiddenToWordPressEvent $event) {
            $event->val = $event->val.':filtered_by_listener';
        });
        
        $result = $dispatcher->dispatch(new ForbiddenToWordPressEvent('FOOBAR'));
        
        $this->assertInstanceOf(ForbiddenToWordPressEvent::class, $result);
        $this->assertSame('FOOBAR:filtered_by_listener', $result->val);
        
        $this->assertSame(0, $count, 'WordPress filter was called when it was forbidden.');
    }
    
    /** @test */
    public function event_objects_that_are_not_mutable_but_open_to_wordpress_will_converted_into_immutable_objects()
    {
        $count = 0;
        add_action(ActionEvent::class, function ($event) use (&$count) {
            $count++;
            $this->assertInstanceOf(ImmutableEvent::class, $event);
        });
        
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->dispatch(new ActionEvent('foo', 'bar', 'baz'));
        
        $this->assertSame(1, $count, 'WordPress action was not called.');
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
        
        $dispatcher->listen('my_plugin_user_created', function (UserCreated $event) {
            $this->respondedToEvent($event->getName(), 'closure1', $event->user_name);
        });
        
        $dispatcher->dispatch(new UserCreated('calvin'));
        
        $this->assertListenerRun('my_plugin_user_created', 'closure1', 'calvin');
    }
    
    private function getDispatcher($container = null) :EventDispatcher
    {
        return new EventDispatcher(
            new ParameterBasedListenerFactory($container ?? new IlluminateContainerAdapter())
        );
    }
    
}

class UserCreated implements Event
{
    
    use ClassAsPayload;
    
    public $user_name;
    
    public function __construct($user_name)
    {
        $this->user_name = $user_name;
    }
    
    public function getName() :string
    {
        return 'my_plugin_user_created';
    }
    
}

class GreaterThenThree implements Event, DispatchesConditionally
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public int $val;
    
    public function __construct(int $val)
    {
        $this->val = $val;
    }
    
    public function shouldDispatch() :bool
    {
        return $this->val > 3;
    }
    
}

class FooEvent implements Event
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $val;
    
    public function __construct($val)
    {
        $this->val = $val;
    }
    
}

class MutableEvent implements Event, Mutable
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $val;
    
    public function __construct($val)
    {
        $this->val = $val;
    }
    
}

class FilterableEvent implements Mutable, Event
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $val;
    
    public function __construct($val)
    {
        $this->val = $val;
    }
    
}

class ActionEvent implements Event
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $foo;
    public $bar;
    private $baz;
    
    public function __construct($foo, $bar, $baz)
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->baz = $baz;
    }
    
}

class ForbiddenToWordPressEvent implements Event, Mutable, IsForbiddenToWordPress
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $val;
    
    public function __construct($val)
    {
        $this->val = $val;
    }
    
}

class ClassListener
{
    
    use AssertListenerResponse;
    
    public function handle(FooEvent $event)
    {
        $this->respondedToEvent(FooEvent::class, ClassListener::class, $event->val);
    }
    
    public function customHandleMethod(FooEvent $event)
    {
        $this->respondedToEvent(FooEvent::class, ClassListener::class, $event->val);
    }
    
}

class ClassListener2
{
    
    use AssertListenerResponse;
    
    public function handle(FooEvent $event)
    {
        $this->respondedToEvent(FooEvent::class, ClassListener2::class, $event->val);
    }
    
}

class ListenerWithNoHandleMethod
{

}

class InvokableListener
{
    
    use AssertListenerResponse;
    
    public function __invoke($foo, $bar)
    {
        $this->respondedToEvent('foo_event', static::class, $foo.$bar);
    }
    
}

class LogEvent1 implements LoggableEvent
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    private $message;
    
    public function __construct($message)
    {
        $this->message = $message;
    }
    
    public function message()
    {
        return $this->message;
    }
    
}

class LogEvent2 implements LoggableEvent
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    private $message;
    
    public function __construct($message)
    {
        $this->message = $message;
    }
    
    public function message()
    {
        return $this->message;
    }
    
}

abstract class AbstractLogin implements Event
{
    
    abstract public function message();
    
}

class PasswordLogin extends AbstractLogin
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public function message()
    {
        return 'password login';
    }
    
}
