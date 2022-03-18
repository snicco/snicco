<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use LogicException;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\BetterWPHooks\EventMapping\MappedFilter;
use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\BetterWPHooks\Exception\CantCreateMappedEvent;
use Snicco\Component\BetterWPHooks\WPHookAPI;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\ListenerFactory\NewableListenerFactory;
use stdClass;

use function add_action;
use function add_filter;
use function apply_filters;
use function apply_filters_ref_array;
use function do_action;
use function implode;
use function sprintf;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * @psalm-suppress UnusedClosureParam
 *
 * @internal
 */
final class EventMapperTest extends WPTestCase
{

    private EventMapper $event_mapper;

    private BaseEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new BaseEventDispatcher(new NewableListenerFactory());
        $this->event_mapper = new EventMapper($this->dispatcher, new WPHookAPI());
    }

    /**
     * ACTIONS.
     */

    /**
     * @test
     */
    public function mapped_actions_only_dispatch_for_their_hook(): void
    {
        $this->event_mapper->map('foo', EmptyActionEvent::class);

        $run = false;
        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$run): void {
            $run = true;
        });

        do_action('bogus');

        $this->assertFalse($run);
    }

    /**
     * @test
     */
    public function that_a_wordpress_action_can_be_mapped_to_a_custom_event_and_the_event_will_dispatch(): void
    {
        $this->event_mapper->map('empty', EmptyActionEvent::class);

        $run = false;
        $this->dispatcher->listen(EmptyActionEvent::class, function (EmptyActionEvent $event) use (&$run): void {
            $this->assertSame('foo', $event->value);
            $run = true;
        });

        do_action('empty');

        $this->assertTrue($run);
    }

    /**
     * @test
     */
    public function that_arguments_from_actions_are_passed_to_the_event(): void
    {
        $this->event_mapper->map('foo_action', FooActionEvent::class);

        $run = false;
        $this->dispatcher->listen(function (FooActionEvent $event) use (&$run): void {
            $this->assertSame('foobarbaz', $event->value());
            $run = true;
        });

        do_action('foo_action', 'foo', 'bar', 'baz');

        $this->assertTrue($run);

        $run = false;

        $this->event_mapper->map('foo_action_array', ActionWithArrayArguments::class);

        $this->dispatcher->listen(function (ActionWithArrayArguments $event) use (&$run): void {
            $this->assertSame('foo|bar:baz', $event->message);
            $run = true;
        });
        do_action('foo_action_array', ['foo', 'bar'], 'baz');
    }

    /**
     * @test
     */
    public function events_mapped_to_a_wordpress_action_are_passed_by_reference_between_listeners(): void
    {
        $this->event_mapper->map('empty', EmptyActionEvent::class);

        $run = false;

        $this->dispatcher->listen(function (EmptyActionEvent $event): void {
            $event->value = 'foobar';
        });

        $this->dispatcher->listen(function (EmptyActionEvent $event) use (&$run): void {
            $this->assertSame('foobar', $event->value);
            $run = true;
        });

        do_action('empty');

        $this->assertTrue($run);
    }

    /**
     * @test
     */
    public function the_mapping_priority_can_be_customized(): void
    {
        $count = 0;
        add_action('empty', function () use (&$count): void {
            ++$count;
        }, 5);

        $this->event_mapper->map('empty', EmptyActionEvent::class, 4);

        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count): void {
            $this->assertSame(0, $count, 'Priority mapping did not work correctly.');
        });

        do_action('empty');

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function two_different_custom_events_can_be_mapped_to_one_action(): void
    {
        $count = 0;
        add_action('empty', function () use (&$count): void {
            ++$count;
        }, 5);

        $this->event_mapper->map('empty', EmptyActionEvent::class, 4);
        $this->event_mapper->map('empty', EmptyActionEvent2::class, 6);

        $this->dispatcher->listen(function (EmptyActionEvent $event) use (&$count): void {
            $this->assertSame(0, $count, 'Priority mapping did not work correctly.');
            ++$count;
        });

        $this->dispatcher->listen(function (EmptyActionEvent2 $event) use (&$count): void {
            $this->assertSame(2, $count, 'Priority mapping did not work correctly.');
            ++$count;
        });

        do_action('empty');

        $this->assertSame(3, $count);
    }

    /**
     * @test
     */
    public function mapped_actions_are_not_accessible_with_wordpress_plugin_functions(): void
    {
        $count = 0;

        add_action(EmptyActionEvent::class, function () use (&$count): void {
            ++$count;
        }, 1);

        $this->event_mapper->map('action', EmptyActionEvent::class);

        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count): void {
            $this->assertSame(0, $count);
            ++$count;
        });

        do_action('action');

        $this->assertSame(1, $count);
    }

    /**
     * FILTERS.
     */

    /**
     * @test
     */
    public function mapped_filters_only_dispatch_for_their_hook(): void
    {
        $this->event_mapper->map('foo', EmptyActionEvent::class);

        $this->dispatcher->listen(EmptyActionEvent::class, function (EmptyActionEvent $event): void {
            $event->value = 'bar';
        });

        $res = apply_filters('bogus', 'foo');

        $this->assertSame('foo', $res);
    }

    /**
     * @test
     */
    public function a_wordpress_filter_can_be_mapped_to_a_custom_event(): void
    {
        $this->event_mapper->map('filter', EventFilterWithNoArgs::class);

        $this->dispatcher->listen(function (EventFilterWithNoArgs $event): void {
            $event->filterable_value .= 'bar';
        });
        $this->dispatcher->listen(function (EventFilterWithNoArgs $event): void {
            $event->filterable_value .= 'baz';
        });

        $final_value = apply_filters('filter', 'foo');

        $this->assertSame('foobarbaz', $final_value);
    }

    /**
     * @test
     */
    public function the_priority_can_be_customized_for_a_mapped_filter(): void
    {
        add_filter('filter', fn(string $value): string => $value . '_wp_filtered_1', 4, 1000);

        add_filter('filter', fn(string $value): string => $value . '_wp_filtered_2', 6, 1000);

        $this->event_mapper->map('filter', EventFilterWithNoArgs::class, 5);

        $this->dispatcher->listen(function (EventFilterWithNoArgs $event): void {
            $event->filterable_value .= 'bar';
        });
        $this->dispatcher->listen(function (EventFilterWithNoArgs $event): void {
            $event->filterable_value .= 'baz';
        });

        $final_value = apply_filters('filter', 'foo');

        $this->assertSame('foo_wp_filtered_1barbaz_wp_filtered_2', $final_value);
    }

    /**
     * @test
     */
    public function two_different_custom_events_can_be_mapped_to_a_wordpress_filter(): void
    {
        add_filter('filter', fn(string $value): string => $value . '_wp_filtered_1_', 4, 1000);

        add_filter('filter', fn(string $value): string => $value . '_wp_filtered_2_', 6, 1000);

        $this->event_mapper->map('filter', EventFilter1::class, 5);
        $this->event_mapper->map('filter', EventFilter2::class, 7);

        $this->dispatcher->listen(function (EventFilter1 $event): void {
            $event->foo .= $event->bar;
        });

        $this->dispatcher->listen(function (EventFilter2 $event): void {
            $event->foo .= $event->bar;
        });

        $final_value = apply_filters('filter', 'foo', 'bar');

        $this->assertSame('foo_wp_filtered_1_bar_wp_filtered_2_bar', $final_value);
    }

    /**
     * MAP_FIRST.
     */

    /**
     * @test
     */
    public function test_map_first_if_no_other_callback_present(): void
    {
        $count = 0;

        $this->event_mapper->mapFirst('wp_hook', EmptyActionEvent::class);

        add_action('wp_hook', function () use (&$count): void {
            ++$count;
        }, PHP_INT_MIN);

        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count): void {
            $this->assertSame(0, $count, 'Mapped Event did not run first.');
            ++$count;
        });

        do_action('wp_hook');

        $this->assertSame(2, $count);
    }

    /**
     * @test
     */
    public function test_map_first_if_another_callback_is_registered_before_with_int_min(): void
    {
        $count = 0;

        add_action('wp_hook', function () use (&$count): void {
            ++$count;
        }, PHP_INT_MIN, 10);

        $this->event_mapper->mapFirst('wp_hook', EmptyActionEvent::class);

        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count): void {
            $this->assertSame(0, $count, 'Mapped Event did not run first.');
            ++$count;
        });

        do_action('wp_hook');

        $this->assertSame(2, $count);
    }

    /**
     * @test
     */
    public function test_map_first_if_another_callback_is_registered_before(): void
    {
        $count = 0;

        add_action('wp_hook', function () use (&$count): void {
            ++$count;
        }, 10, 1);

        $this->event_mapper->mapFirst('wp_hook', EmptyActionEvent::class);

        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count): void {
            $this->assertSame(0, $count, 'Mapped Event did not run first.');
            ++$count;
        });

        do_action('wp_hook');

        $this->assertSame(2, $count);
    }

    /**
     * @test
     */
    public function test_map_first_if_registered_with_php_int_min(): void
    {
        $count = 0;

        add_action('wp_hook', function () use (&$count): void {
            $this->assertSame(1, $count);
            ++$count;
        }, PHP_INT_MIN);

        add_action('wp_hook', function () use (&$count): void {
            $this->assertSame(2, $count);
            ++$count;
        }, PHP_INT_MIN);

        add_action('wp_hook', function () use (&$count): void {
            $this->assertSame(3, $count);
            ++$count;
        }, 20);

        add_action('wp_hook', function () use (&$count): void {
            $this->assertSame(4, $count);
            ++$count;
        }, 50);

        $this->event_mapper->mapFirst('wp_hook', EmptyActionEvent::class);

        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count): void {
            $this->assertSame(0, $count, 'Mapped Event did not run first.');
            ++$count;
        });

        do_action('wp_hook');

        $this->assertSame(5, $count);
    }

    /**
     * @test
     */
    public function test_exception_if_filtering_during_the_same_filter(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                "You can can't map the event [%s] to the hook [wp_hook] after it was fired.",
                EmptyActionEvent::class
            )
        );

        add_action('wp_hook', function (): void {
            $this->event_mapper->mapFirst('wp_hook', EmptyActionEvent::class);
        });

        do_action('wp_hook');
    }

    /**
     * MAP_LAST.
     */

    /**
     * @test
     */
    public function test_map_last_if_no_other_callback_present(): void
    {
        $count = 0;

        $this->event_mapper->mapLast('wp_hook', EmptyActionEvent::class);

        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count): void {
            ++$count;
        });

        do_action('wp_hook');

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function test_ensure_last_with_callback_added_after(): void
    {
        $count = 0;

        $this->event_mapper->mapLast('wp_hook', EmptyActionEvent::class);
        $this->dispatcher->listen(EmptyActionEvent::class, function () use (&$count): void {
            $this->assertSame(4, $count, 'Mapped Event did not run last.');
            ++$count;
        });

        add_action('wp_hook', function () use (&$count): void {
            $this->assertSame(0, $count);
            ++$count;
        }, 50);

        add_action('wp_hook', function () use (&$count): void {
            $this->assertSame(1, $count);
            ++$count;
        }, 100);

        add_action('wp_hook', function () use (&$count): void {
            $this->assertSame(2, $count);
            ++$count;
        }, 200);

        add_action('wp_hook', function () use (&$count): void {
            $this->assertSame(3, $count);
            ++$count;
        }, PHP_INT_MAX);

        do_action('wp_hook');

        $this->assertSame(5, $count);
    }

    /**
     * VALIDATION.
     */

    /**
     * @test
     */
    public function a_mapped_event_has_to_have_on_of_the_valid_interfaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('has to implement either');
        $this->event_mapper->map('foobar', NormalEvent::class);
    }

    /**
     * @test
     */
    public function cant_map_the_same_filter_twice_to_the_same_custom_event(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Tried to map the event class [%s] twice to the [foobar] filter.', EventFilter1::class)
        );
        $this->event_mapper->map('foobar', EventFilter1::class);
        $this->event_mapper->map('foobar', EventFilter1::class);
    }

    /**
     * @test
     */
    public function cant_map_the_same_action_twice_to_the_same_custom_event(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Tried to map the event class [%s] twice to the [foobar] hook.', FooActionEvent::class)
        );
        $this->event_mapper->map('foobar', FooActionEvent::class);
        $this->event_mapper->map('foobar', FooActionEvent::class);
    }

    /**
     * @test
     *
     * @psalm-suppress UndefinedClass
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function cant_map_to_a_non_existing_class(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The event class [%s] does not exist.', 'Bogus'));
        $this->event_mapper->map('foobar', 'Bogus');
    }

    /**
     * @test
     */
    public function conditional_filters_are_prevented_if_conditions_dont_match(): void
    {
        $this->event_mapper->map('filter', ConditionalFilter::class);

        $this->dispatcher->listen(ConditionalFilter::class, function (ConditionalFilter $event): void {
            $event->value1 = 'CUSTOM';
        });

        add_filter('filter', function ($value1, $value2): string {
            $this->assertSame('foo', $value1);
            $this->assertSame('PREVENT', $value2);

            return $value1 . ':filtered';
        }, 10, 2);

        $final_value = apply_filters('filter', 'foo', 'PREVENT');

        $this->assertSame('foo:filtered', $final_value);
    }

    /**
     * @test
     */
    public function conditional_filter_events_are_dispatched_if_conditions_match(): void
    {
        $this->event_mapper->map('filter', ConditionalFilter::class);

        $this->dispatcher->listen(ConditionalFilter::class, function (ConditionalFilter $event): void {
            $event->value1 = 'CUSTOM';
        });

        add_filter('filter', function ($value1, $value2): string {
            $this->assertSame('CUSTOM', $value1);
            $this->assertSame('bar', $value2);

            return $value1 . ':filtered';
        }, 10, 2);

        $final_value = apply_filters('filter', 'foo', 'bar');

        $this->assertSame('CUSTOM:filtered', $final_value);
    }

    /**
     * @test
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     */
    public function conditional_actions_are_prevented_if_conditions_dont_match(): void
    {
        $this->event_mapper->map('action', ConditionalAction::class);

        $count = 0;
        $this->dispatcher->listen(ConditionalAction::class, function (ConditionalAction $event): void {
            $event->value = 'did run';
        });


        add_action('action', function (string $value) use (&$count): void {
            $this->assertSame('PREVENT', $value);
            ++$count;
        }, 10, 2);

        do_action('action', 'PREVENT');

        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_the_provided_arguments_are_invalid_for_the_mapped_event(): void
    {
        $this->event_mapper->map('foo_filter', EventFilter1::class);

        $this->expectException(CantCreateMappedEvent::class);
        $this->expectExceptionMessage(
            sprintf(
                "The mapped event [%s] could not be instantiated with the passed received arguments from WordPress.\nReceived [string,object].",
                EventFilter1::class
            )
        );

        apply_filters_ref_array('foo_filter', ['foo', new stdClass()]);
    }

    /**
     * @test
     */
    public function test_exception_if_current_filter_is_removed_for_some_reason_during_on_the_fly_mapping(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('$current_filter should never be null during mapping.');

        $this->event_mapper->mapLast('foo', EmptyActionEvent::class);
        add_action('foo', function (): void {
            $GLOBALS['wp_current_filter'] = [];
        }, 10);

        do_action('foo');
    }
}

final class ConditionalFilter implements MappedFilter
{
    use ClassAsName;
    use ClassAsPayload;

    public string $value1;

    private string $value2;

    public function __construct(string $value1, string $value2)
    {
        $this->value1 = $value1;
        $this->value2 = $value2;
    }

    public function shouldDispatch(): bool
    {
        return 'PREVENT' !== $this->value2;
    }

    public function filterableAttribute(): string
    {
        return $this->value1;
    }
}

final class ConditionalAction implements MappedHook
{
    use ClassAsName;
    use ClassAsPayload;

    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function shouldDispatch(): bool
    {
        return 'PREVENT' !== $this->value;
    }
}

final class NormalEvent implements Event
{
    use ClassAsName;
    use ClassAsPayload;
}

final class EventFilterWithNoArgs implements MappedFilter
{
    use ClassAsName;
    use ClassAsPayload;

    public string $filterable_value;

    public function __construct(string $filterable_value)
    {
        $this->filterable_value = $filterable_value;
    }

    public function filterableAttribute(): string
    {
        return $this->filterable_value;
    }

    public function shouldDispatch(): bool
    {
        return true;
    }
}

final class EventFilter1 implements MappedFilter
{
    use ClassAsName;
    use ClassAsPayload;

    public string $foo;

    public string $bar;

    public function __construct(string $foo, string $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function filterableAttribute(): string
    {
        return $this->foo;
    }

    public function shouldDispatch(): bool
    {
        return true;
    }
}

final class EventFilter2 implements MappedFilter
{
    use ClassAsName;
    use ClassAsPayload;

    public string $foo;

    public string $bar;

    public function __construct(string $foo, string $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function filterableAttribute(): string
    {
        return $this->foo;
    }

    public function shouldDispatch(): bool
    {
        return true;
    }
}

final class EmptyActionEvent implements MappedHook
{
    use ClassAsName;
    use ClassAsPayload;

    public string $value = 'foo';

    public function shouldDispatch(): bool
    {
        return true;
    }
}

final class EmptyActionEvent2 implements MappedHook
{
    use ClassAsName;
    use ClassAsPayload;

    public string $value = 'bar';

    public function shouldDispatch(): bool
    {
        return true;
    }
}

final class FooActionEvent implements MappedHook
{
    use ClassAsName;
    use ClassAsPayload;

    private string $foo;

    private string $bar;

    private string $baz;

    public function __construct(string $foo, string $bar, string $baz)
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->baz = $baz;
    }

    public function value(): string
    {
        return $this->foo . $this->bar . $this->baz;
    }

    public function shouldDispatch(): bool
    {
        return true;
    }
}

final class ActionWithArrayArguments implements MappedHook
{
    use ClassAsName;
    use ClassAsPayload;

    public string $message;

    /**
     * @param string[] $words
     */
    public function __construct(array $words, string $suffix)
    {
        $this->message = implode('|', $words) . ':' . $suffix;
    }

    public function shouldDispatch(): bool
    {
        return true;
    }
}
