<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\integration;

use LogicException;
use Codeception\TestCase\WPTestCase;
use Snicco\EventDispatcher\EventMapper;
use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;
use Tests\BetterWPHooks\fixtures\FooEvent;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\MappedFilter;
use Snicco\EventDispatcher\Contracts\MappedAction;
use Snicco\EventDispatcher\Dispatcher\EventDispatcher;
use Tests\BetterWPHooks\helpers\AssertListenerResponse;
use Snicco\EventDispatcher\Implementations\ParameterBasedListenerFactory;

class EventMapperTest extends WPTestCase
{
    
    use AssertListenerResponse;
    
    private EventMapper     $event_mapper;
    private EventDispatcher $dispatcher;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->dispatcher = new EventDispatcher(
            new ParameterBasedListenerFactory()
        );
        $this->event_mapper = new EventMapper($this->dispatcher);
        $this->resetListenersResponses();
    }
    
    /**
     * ACTIONS
     */
    
    /** @test */
    public function mapped_actions_only_dispatch_for_their_hook()
    {
        $this->event_mapper->map('foo', EmptyActionEvent::class);
        
        $this->dispatcher->listen(EmptyActionEvent::class, function () {
            $this->respondedToEvent(FooEvent::class, 'closure1', 'foo');
        });
        
        do_action('bogus');
        
        $this->assertListenerNotRun(EmptyActionEvent::class, 'closure1');
    }
    
    /** @test */
    public function a_wordpress_action_can_be_mapped_to_a_custom_event_and_the_event_will_dispatch()
    {
        $this->event_mapper->map('empty', EmptyActionEvent::class);
        
        $this->dispatcher->listen(EmptyActionEvent::class, function () {
            $this->respondedToEvent(EmptyActionEvent::class, 'closure1', 'foo');
        });
        
        do_action('empty');
        
        $this->assertListenerRun(EmptyActionEvent::class, 'closure1', 'foo');
    }
    
    /** @test */
    public function arguments_from_actions_are_passed_to_the_event()
    {
        $this->event_mapper->map('foo_action', FooActionEvent::class);
        
        $this->dispatcher->listen(function (FooActionEvent $event) {
            $this->respondedToEvent(FooActionEvent::class, 'closure1', $event->value());
        });
        
        do_action('foo_action', 'foo', 'bar', 'baz');
        
        $this->assertListenerRun(FooActionEvent::class, 'closure1', 'foobarbaz');
        
        $this->resetListenersResponses();
        
        $this->event_mapper->map('foo_action_array', ActionWithArrayArguments::class);
        $this->dispatcher->listen(function (ActionWithArrayArguments $event) {
            $this->respondedToEvent(ActionWithArrayArguments::class, 'closure2', $event->message);
        });
        do_action('foo_action_array', ['foo', 'bar'], 'baz');
        
        $this->assertListenerRun(ActionWithArrayArguments::class, 'closure2', 'foo|bar:baz');
    }
    
    /** @test */
    public function events_mapped_to_a_wordpress_action_are_immutable()
    {
        $this->event_mapper->map('empty', EmptyActionEvent::class);
        
        $this->dispatcher->listen(function (EmptyActionEvent $event) {
            $val = $event->value;
            $event->value = 'foobar';
            $this->respondedToEvent(EmptyActionEvent::class, 'closure1', $val);
        });
        
        $this->dispatcher->listen(function (EmptyActionEvent $event) {
            $this->respondedToEvent(EmptyActionEvent::class, 'closure2', $event->value);
        });
        
        do_action('empty');
        
        $this->assertListenerRun(EmptyActionEvent::class, 'closure1', 'foo');
        
        // The value is still foo since the event is immutable.
        $this->assertListenerRun(EmptyActionEvent::class, 'closure2', 'foo');
    }
    
    /** @test */
    public function the_mapping_priority_can_be_customized()
    {
        $count = 0;
        add_action('empty', function () use (&$count) {
            $count++;
        }, 5);
        
        $this->event_mapper->map('empty', EmptyActionEvent::class, 4);
        
        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count) {
            $this->assertSame(0, $count, "Priority mapping did not work correctly.");
            $this->respondedToEvent(EmptyActionEvent::class, 'closure1', 'foo');
        });
        
        do_action('empty');
        
        $this->assertListenerRun(EmptyActionEvent::class, 'closure1', 'foo');
    }
    
    /** @test */
    public function two_custom_events_can_be_mapped_to_one_action()
    {
        $count = 0;
        add_action('empty', function () use (&$count) {
            $count++;
        }, 5);
        
        $this->event_mapper->map('empty', EmptyActionEvent::class, 4);
        $this->event_mapper->map('empty', EmptyActionEvent2::class, 6);
        
        $this->dispatcher->listen(function (EmptyActionEvent $event) use (&$count) {
            $this->assertSame(0, $count, 'Priority mapping did not work correctly.');
            $count++;
        });
        
        $this->dispatcher->listen(function (EmptyActionEvent2 $event) use (&$count) {
            $this->assertSame(2, $count, 'Priority mapping did not work correctly.');
            $count++;
        });
        
        do_action('empty');
        
        $this->assertSame(3, $count);
    }
    
    /** @test */
    public function mapped_actions_are_not_accessible_with_wordpress_plugin_functions()
    {
        $count = 0;
        
        add_action(EmptyActionEvent::class, function () use (&$count) {
            $count++;
        }, 1);
        
        $this->event_mapper->map('action', EmptyActionEvent::class);
        
        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count) {
            $this->assertSame(0, $count);
            $count++;
        });
        
        do_action('action');
        
        $this->assertSame(1, $count);
    }
    
    /**
     * FILTERS
     */
    
    /** @test */
    public function mapped_filters_only_dispatch_for_their_hook()
    {
        $this->event_mapper->map('foo', EmptyActionEvent::class);
        
        $this->dispatcher->listen(EmptyActionEvent::class, function () {
            $this->respondedToEvent(FooEvent::class, 'closure1', 'foo');
        });
        
        apply_filters('bogus', 'foo');
        
        $this->assertListenerNotRun(EmptyActionEvent::class, 'closure1');
    }
    
    /** @test */
    public function a_wordpress_filter_can_be_mapped_to_a_custom_event()
    {
        $this->event_mapper->map('filter', EventFilterWithNoArgs::class);
        
        $this->dispatcher->listen(function (EventFilterWithNoArgs $event) {
            $event->filterable_value = $event->filterable_value.'bar';
        });
        $this->dispatcher->listen(function (EventFilterWithNoArgs $event) {
            $event->filterable_value = $event->filterable_value.'baz';
        });
        
        $final_value = apply_filters('filter', 'foo');
        
        $this->assertSame('foobarbaz', $final_value);
    }
    
    /** @test */
    public function the_priority_can_be_customized_for_a_mapped_filter()
    {
        add_filter('filter', function (string $value) {
            return $value.'_wp_filtered_1';
        }, 4, 1000);
        
        add_filter('filter', function (string $value) {
            return $value.'_wp_filtered_2';
        }, 6, 1000);
        
        $this->event_mapper->map('filter', EventFilterWithNoArgs::class, 5);
        
        $this->dispatcher->listen(function (EventFilterWithNoArgs $event) {
            $event->filterable_value = $event->filterable_value.'bar';
        });
        $this->dispatcher->listen(function (EventFilterWithNoArgs $event) {
            $event->filterable_value = $event->filterable_value.'baz';
        });
        
        $final_value = apply_filters('filter', 'foo');
        
        $this->assertSame('foo_wp_filtered_1barbaz_wp_filtered_2', $final_value);
    }
    
    /** @test */
    public function two_filters_can_be_mapped_to_a_wordpress_filter()
    {
        add_filter('filter', function (string $value) {
            return $value.'_wp_filtered_1_';
        }, 4, 1000);
        
        add_filter('filter', function (string $value) {
            return $value.'_wp_filtered_2_';
        }, 6, 1000);
        
        $this->event_mapper->map('filter', EventFilter1::class, 5);
        $this->event_mapper->map('filter', EventFilter2::class, 7);
        
        $this->dispatcher->listen(function (EventFilter1 $event) {
            $event->foo = $event->foo.$event->bar;
        });
        
        $this->dispatcher->listen(function (EventFilter2 $event) {
            $event->foo = $event->foo.$event->bar;
        });
        
        $final_value = apply_filters('filter', 'foo', 'bar');
        
        $this->assertSame('foo_wp_filtered_1_bar_wp_filtered_2_bar', $final_value);
    }
    
    /**
     * MAP_FIRST
     */
    
    /** @test */
    public function test_map_first_if_no_other_callback_present()
    {
        $count = 0;
        
        $this->event_mapper->mapFirst('wp_hook', EmptyActionEvent::class);
        
        add_action('wp_hook', function () use (&$count) {
            $count++;
        }, PHP_INT_MIN);
        
        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count) {
            $this->assertSame(0, $count, 'Mapped Event did not run first.');
            $count++;
        });
        
        do_action('wp_hook');
        
        $this->assertSame(2, $count);
    }
    
    /** @test */
    public function test_map_first_if_already_present()
    {
        $count = 0;
        
        add_action('wp_hook', function () use (&$count) {
            $count++;
        }, 10);
        
        $this->event_mapper->mapFirst('wp_hook', EmptyActionEvent::class);
        
        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count) {
            $this->assertSame(0, $count, "Mapped Event did not run first.");
            $count++;
        });
        
        do_action('wp_hook');
        
        $this->assertSame(2, $count);
    }
    
    /** @test */
    public function test_map_first_if_registered_with_php_int_min()
    {
        $count = 0;
        
        add_action('wp_hook', function () use (&$count) {
            $this->assertSame(1, $count);
            $count++;
        }, PHP_INT_MIN);
        
        add_action('wp_hook', function () use (&$count) {
            $this->assertSame(2, $count);
            $count++;
        }, PHP_INT_MIN);
        
        add_action('wp_hook', function () use (&$count) {
            $this->assertSame(3, $count);
            $count++;
        }, 20);
        
        add_action('wp_hook', function () use (&$count) {
            $this->assertSame(4, $count);
            $count++;
        }, 50);
        
        $this->event_mapper->mapFirst('wp_hook', EmptyActionEvent::class);
        
        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count) {
            $this->assertSame(0, $count, 'Mapped Event did not run first.');
            $count++;
        });
        
        do_action('wp_hook');
        
        $this->assertSame(5, $count);
    }
    
    /** @test */
    public function test_exception_if_filtering_during_the_same_same_filter()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                "You can can't map the event [%s] to the hook [wp_hook] after it was fired.",
                EmptyActionEvent::class
            )
        );
        
        add_action('wp_hook', function () {
            $this->event_mapper->mapFirst('wp_hook', EmptyActionEvent::class);
        });
        
        do_action('wp_hook');
    }
    
    /**
     * MAP_LAST
     */
    
    /** @test */
    public function test_map_last_if_no_other_callback_present()
    {
        $count = 0;
        
        $this->event_mapper->mapLast('wp_hook', EmptyActionEvent::class);
        
        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count) {
            $count++;
        });
        
        do_action('wp_hook');
        
        $this->assertSame(1, $count);
    }
    
    /** @test */
    public function test_ensure_last_with_callback_added_after()
    {
        $count = 0;
        
        $this->event_mapper->mapLast('wp_hook', EmptyActionEvent::class);
        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count) {
            $this->assertSame(4, $count, 'Mapped Event did not run last.');
            $count++;
        });
        
        add_action('wp_hook', function () use (&$count) {
            $this->assertSame(0, $count);
            $count++;
        }, 50);
        
        add_action('wp_hook', function () use (&$count) {
            $this->assertSame(1, $count);
            $count++;
        }, 100);
        
        add_action('wp_hook', function () use (&$count) {
            $this->assertSame(2, $count);
            $count++;
        }, 200);
        
        add_action('wp_hook', function () use (&$count) {
            $this->assertSame(3, $count);
            $count++;
        }, PHP_INT_MAX);
        
        do_action('wp_hook');
        
        $this->assertSame(5, $count);
    }
    
    /**
     * VALIDATION
     */
    
    /** @test */
    public function a_mapped_event_has_to_have_on_of_the_valid_interfaces()
    {
        $this->expectExceptionMessage(
            "The event [".
            NormalEvent::class
            ."] has to implement either the [MappedAction] or the [MappedFilter] interface."
        );
        $this->event_mapper->map('foobar', NormalEvent::class);
    }
    
    /** @test */
    public function cant_map_the_same_event_twice()
    {
        $this->expectExceptionMessage(
            sprintf(
                "Tried to map the event class [%s] twice to the [foobar] filter.",
                EventFilter1::class
            )
        );
        $this->event_mapper->map('foobar', EventFilter1::class);
        $this->event_mapper->map('foobar', EventFilter1::class);
    }
    
}

class NormalEvent implements Event
{
    
    use ClassAsName;
    use ClassAsPayload;
}

class EventFilterWithNoArgs implements MappedFilter
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $filterable_value;
    
    public function __construct($filterable_value)
    {
        $this->filterable_value = $filterable_value;
    }
    
    public function filterableAttribute()
    {
        return $this->filterable_value;
    }
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}

class EventFilter1 implements MappedFilter
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $foo;
    public $bar;
    
    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
    
    public function filterableAttribute()
    {
        return $this->foo;
    }
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}

class EventFilter2 implements MappedFilter
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $foo;
    public $bar;
    
    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
    
    public function filterableAttribute()
    {
        return $this->foo;
    }
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}

class EmptyActionEvent implements MappedAction
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $value = 'foo';
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}

class EmptyActionEvent2 implements MappedAction
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $value = 'bar';
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}

class FooActionEvent implements MappedAction
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    private $foo;
    private $bar;
    private $baz;
    
    public function __construct($foo, $bar, $baz)
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->baz = $baz;
    }
    
    public function value()
    {
        return $this->foo.$this->bar.$this->baz;
    }
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}

class ActionWithArrayArguments implements MappedAction
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $message;
    
    public function __construct(array $words, $suffix)
    {
        $this->message = implode('|', $words).':'.$suffix;
    }
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}
