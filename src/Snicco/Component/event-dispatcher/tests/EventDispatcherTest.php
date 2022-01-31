<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests;

use stdClass;
use PHPUnit\Framework\TestCase;
use Snicco\Component\EventDispatcher\GenericEvent;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Exception\CantRemove;
use Snicco\Component\EventDispatcher\Exception\InvalidListener;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\FooEvent;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\BarEvent;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\LogEvent1;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\LogEvent2;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\UserCreated;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\MutableEvent;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\LoggableEvent;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\AbstractLogin;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\PasswordLogin;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\FilterableEvent;
use Snicco\Component\EventDispatcher\Tests\fixtures\Listener\ClassListener;
use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;
use Snicco\Component\EventDispatcher\ListenerFactory\NewableListenerFactory;
use Snicco\Component\EventDispatcher\Tests\fixtures\Listener\ClassListener2;
use Snicco\Component\EventDispatcher\Tests\fixtures\Listener\InvokableListener;
use Snicco\Component\EventDispatcher\Tests\fixtures\Listener\ListenerWithoutMethod;

class EventDispatcherTest extends TestCase
{
    
    use AssertListenerResponse;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->resetListenersResponses();
    }
    
    protected function tearDown() :void
    {
        $this->resetListenersResponses();
        parent::tearDown();
    }
    
    /** @test */
    public function listeners_are_run_for_matching_events()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, function (FooEvent $event) {
            $this->respondedToEvent(FooEvent::class, 'closure1', $event->val);
        });
        
        $dispatcher->dispatch(new FooEvent('FOO'));
        
        $this->assertListenerRun(FooEvent::class, 'closure1', 'FOO');
    }
    
    /** @test */
    public function listeners_dont_respond_to_different_events()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(FooEvent::class, function () {
            $this->respondedToEvent(FooEvent::class, 'closure1', 'bar');
        });
        
        $dispatcher->dispatch(new BarEvent('foo'));
        
        $this->assertListenerNotRun(FooEvent::class, 'closure1');
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
        
        $dispatcher->dispatch(new GenericEvent('foo_event', ['BAR']));
        
        $this->assertListenerRun('foo_event', 'closure1', 'bar');
        $this->assertListenerRun('foo_event', 'closure2', 'baz');
    }
    
    /** @test */
    public function listeners_can_be_added_after_an_event_was_dispatched_already()
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->dispatch(new GenericEvent('foo_event'));
        
        $this->assertListenerNotRun('foo_event', 'closure1');
        
        $dispatcher->listen('foo_event', function () {
            $this->respondedToEvent('foo_event', 'closure1', 'bar');
        });
        
        $dispatcher->dispatch(new GenericEvent('foo_event'));
        
        $this->assertListenerRun('foo_event', 'closure1', 'bar');
    }
    
    /** @test */
    public function arguments_are_passed_to_the_listener()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('foo_event', function ($foo, $bar) {
            $this->respondedToEvent('foo_event', 'closure1', $foo.$bar);
        });
        
        $dispatcher->dispatch(new GenericEvent('foo_event', ['FOO', 'BAR']));
        
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
    public function non_existing_listener_classes_throw_exceptions()
    {
        $dispatcher = $this->getDispatcher();
        
        $this->expectException(InvalidListener::class);
        $this->expectExceptionMessage('The listener [BogusClass] is not a valid class.');
        
        $dispatcher->listen('foo_event', 'BogusClass');
    }
    
    /** @test */
    public function a_missing_invoke_method_will_throw_an_exception()
    {
        $dispatcher = $this->getDispatcher();
        
        $this->expectException(InvalidListener::class);
        $this->expectExceptionMessage(
            sprintf(
                "The listener [%s] does not define the __invoke() method.",
                ListenerWithoutMethod::class
            ),
        );
        
        $dispatcher->listen('foo_event', ListenerWithoutMethod::class);
    }
    
    /** @test */
    public function listeners_can_be_registered_as_strings_if_they_are_invokable()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('foo_event', InvokableListener::class);
        
        $dispatcher->dispatch(new GenericEvent('foo_event', ['foo', 'bar']));
        
        $this->assertListenerRun('foo_event', InvokableListener::class, 'foobar');
    }
    
    /** @test */
    public function an_exception_is_thrown_for_invalid_instance_methods()
    {
        $dispatcher = $this->getDispatcher();
        
        $this->expectException(InvalidListener::class);
        $this->expectExceptionMessage(
            sprintf(
                "The listener class [%s] does not have a [%s] method.",
                ClassListener::class,
                'bogus'
            )
        );
        
        $dispatcher->listen('foo_event', [ClassListener::class, 'bogus']);
    }
    
    /** @test */
    public function an_exception_is_thrown_for_invalid_classes_when_passed_as_array()
    {
        $dispatcher = $this->getDispatcher();
        
        $this->expectException(InvalidListener::class);
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
        
        $this->expectException(CantRemove::class);
        
        $dispatcher->remove(FooEvent::class, ClassListener::class);
    }
    
    /** @test */
    public function a_closure_listener_can_be_marked_as_unremovable()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen(
            FooEvent::class,
            $closure = function () {
            },
            false
        );
        
        $this->expectException(CantRemove::class);
        
        $dispatcher->remove(FooEvent::class, $closure);
    }
    
    /** @test */
    public function all_listeners_receive_the_same_event_object()
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
    public function an_event_can_be_dispatched_with_a_custom_name()
    {
        $dispatcher = $this->getDispatcher();
        
        $dispatcher->listen('my_plugin_user_created', function (UserCreated $event) {
            $this->respondedToEvent($event->name(), 'closure1', $event->user_name);
        });
        
        $dispatcher->dispatch(new UserCreated('calvin'));
        
        $this->assertListenerRun('my_plugin_user_created', 'closure1', 'calvin');
    }
    
    private function getDispatcher() :EventDispatcher
    {
        return new BaseEventDispatcher(
            new NewableListenerFactory()
        );
    }
    
}
