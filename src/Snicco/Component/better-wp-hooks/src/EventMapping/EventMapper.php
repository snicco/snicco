<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\EventMapping;

use Closure;
use InvalidArgumentException;
use LogicException;
use Snicco\Component\BetterWPHooks\EventFactory\MappedEventFactory;
use Snicco\Component\BetterWPHooks\EventFactory\ParameterBasedEventFactory;
use Snicco\Component\BetterWPHooks\ScopableWP;
use Snicco\Component\EventDispatcher\EventDispatcher;

use function array_key_first;

use const PHP_INT_MIN;

/**
 * @api
 */
final class EventMapper
{

    private EventDispatcher $event_dispatcher;
    private ScopableWP $wp;
    private MappedEventFactory $event_factory;

    /**
     * @var array<string,array<string,string>>
     */
    private array $mapped_actions = [];

    /**
     * @var array<string,array<string,string>>
     */
    private array $mapped_filters = [];

    public function __construct(
        EventDispatcher $event_dispatcher,
        ScopableWP $wp,
        ?MappedEventFactory $event_factory = null
    ) {
        $this->event_dispatcher = $event_dispatcher;
        $this->wp = $wp;
        $this->event_factory = $event_factory ?? new ParameterBasedEventFactory();
    }

    /**
     * Map a WordPress hook to a dedicated event class with the provided priority.
     */
    public function map(string $wordpress_hook_name, string $map_to_event_class, int $priority = 10): void
    {
        $this->validate($wordpress_hook_name, $map_to_event_class);
        $this->mapValidated($wordpress_hook_name, $map_to_event_class, $priority);
    }

    /**
     * @throws LogicException If the hook is already mapped to the same event class
     * @throws InvalidArgumentException If $map_to is not either a MappedAction or MappedFilter
     */
    private function validate(string $wordpress_hook_name, string $map_to): void
    {
        if (isset($this->mapped_actions[$wordpress_hook_name][$map_to])) {
            throw new LogicException(
                "Tried to map the event class [$map_to] twice to the [$wordpress_hook_name] hook."
            );
        }

        if (isset($this->mapped_filters[$wordpress_hook_name][$map_to])) {
            throw new LogicException(
                "Tried to map the event class [$map_to] twice to the [$wordpress_hook_name] filter."
            );
        }

        if (!class_exists($map_to)) {
            throw new InvalidArgumentException("The event class [$map_to] does not exist.");
        }

        $interfaces = class_implements($map_to);

        if (in_array(MappedAction::class, $interfaces, true)) {
            $this->mapped_actions[$wordpress_hook_name][$map_to] = $map_to;
            return;
        }
        if (in_array(MappedFilter::class, $interfaces, true)) {
            $this->mapped_filters[$wordpress_hook_name][$map_to] = $map_to;
            return;
        }

        throw new InvalidArgumentException(
            "The event [$map_to] has to implement either the [MappedAction] or the [MappedFilter] interface."
        );
    }

    private function mapValidated(string $wordpress_hook_name, string $map_to, int $priority): void
    {
        if (isset($this->mapped_actions[$wordpress_hook_name][$map_to])) {
            $this->wp->addAction(
                $wordpress_hook_name,
                $this->dispatchMappedAction($map_to),
                $priority,
                9999
            );
        } else {
            $this->wp->addFilter(
                $wordpress_hook_name,
                $this->dispatchMappedFilter($map_to),
                $priority,
                9999
            );
        }
    }

    private function dispatchMappedAction(string $event_class): Closure
    {
        return function (...$args_from_wordpress_hooks) use ($event_class) {
            // Remove the empty "" that WordPress will pass for actions without any passed arguments.
            if (is_string($args_from_wordpress_hooks[0])
                && empty($args_from_wordpress_hooks[0])
                && count($args_from_wordpress_hooks) === 1) {
                array_shift($args_from_wordpress_hooks);
            }

            $event = $this->event_factory->make(
                $event_class,
                $args_from_wordpress_hooks
            );

            if (!$event->shouldDispatch()) {
                // We don't need to return any values here.
                return;
            }

            $this->event_dispatcher->dispatch($event);
        };
    }

    private function dispatchMappedFilter(string $event_class): Closure
    {
        return function (...$args_from_wordpress_hooks) use ($event_class) {
            $event = $this->event_factory->make(
                $event_class,
                $args_from_wordpress_hooks
            );

            if (!$event->shouldDispatch()) {
                // It's crucial to return the first argument here.
                return $args_from_wordpress_hooks[0];
            }

            $payload = $this->event_dispatcher->dispatch($event);

            if (!$payload instanceof MappedFilter) {
                throw new LogicException(
                    "Mapped Filter [$event_class] has to return an instance of [MappedFilter]."
                );
            }

            return $payload->filterableAttribute();
        };
    }

    /**
     * Map a WordPress hook to a dedicated event class which will ALWAYS be dispatched BEFORE
     * any other callbacks are run for the hook.
     */
    public function mapFirst(string $wordpress_hook_name, string $map_to_event_class): void
    {
        $this->validate($wordpress_hook_name, $map_to_event_class);
        $this->ensureFirst($wordpress_hook_name, $map_to_event_class);
    }

    /**
     * @return void
     */
    private function ensureFirst(string $wordpress_hook_name, string $map_to)
    {
        $filter = $this->wp->currentFilter();
        if ($filter && $filter === $wordpress_hook_name) {
            throw new LogicException(
                "You can can't map the event [$map_to] to the hook [$wordpress_hook_name] after it was fired."
            );
        }

        $wp_hook = $this->wp->getHook($wordpress_hook_name);

        // Unless there is another filter registered with the priority PHP_INT_MIN
        // all we have to do is add our mapped event at this priority.
        // Even if other callback were to be added later with the same priority they would still be run after ours.
        if (!$wp_hook || empty($wp_hook->callbacks)) {
            $this->mapValidated($wordpress_hook_name, $map_to, PHP_INT_MIN);
            return;
        }

        $lowest_priority = array_key_first($wp_hook->callbacks);

        if ($lowest_priority > PHP_INT_MIN) {
            $this->mapValidated($wordpress_hook_name, $map_to, PHP_INT_MIN);
            return;
        }

        // If other filters are already created with the priority PHP_INT_MIN we remove them and
        // add them add the new priority which is PHP_INT_MIN+1.
        $callbacks = $wp_hook->callbacks[$lowest_priority];
        unset($wp_hook->callbacks[$lowest_priority]);

        $this->mapValidated($wordpress_hook_name, $map_to, PHP_INT_MIN);

        $wp_hook->callbacks[$lowest_priority + 1] = $callbacks;

        // This is important in order to keep the relative priority.
        ksort($wp_hook->callbacks, SORT_NUMERIC);
    }

    /**
     * Map a WordPress hook to a dedicated event class which will ALWAYS be dispatched AFTER all
     * other callbacks are run for the hook.
     */
    public function mapLast(string $wordpress_hook_name, string $map_to): void
    {
        $this->validate($wordpress_hook_name, $map_to);
        $this->ensureLast($wordpress_hook_name, $map_to);
    }

    private function ensureLast(string $wordpress_hook_name, string $map_to): void
    {
        $this->wp->addAction($wordpress_hook_name, function (...$args) use ($map_to) {
            // Even if somebody else registered a filter with PHP_INT_MAX our mapped action
            // will be run after the present callback unless it was also added during runtime
            // at the priority PHP_INT_MAX -1 which is highly unlikely.
            $this->mapValidated($this->wp->currentFilter(), $map_to, PHP_INT_MAX);
            return $args[0];
        }, PHP_INT_MAX - 1, 999);
    }

}