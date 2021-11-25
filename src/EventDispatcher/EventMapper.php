<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Closure;
use WP_Hook;
use LogicException;
use InvalidArgumentException;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\EventDispatcher\Contracts\MappedFilter;
use Snicco\EventDispatcher\Contracts\MappedAction;
use Snicco\EventDispatcher\Contracts\MappedEventFactory;
use Snicco\EventDispatcher\Implementations\ParameterBasedEventFactory;

/**
 * @api
 */
final class EventMapper
{
    
    /**
     * @var MappedEventFactory
     */
    private $event_factory;
    
    /**
     * @var Dispatcher
     */
    private $event_dispatcher;
    
    /**
     * @var array
     */
    private $mapped_actions = [];
    
    /**
     * @var array
     */
    private $mapped_filters = [];
    
    public function __construct(Dispatcher $event_dispatcher, ?MappedEventFactory $event_factory = null)
    {
        $this->event_dispatcher = $event_dispatcher;
        $this->event_factory = $event_factory ?? new ParameterBasedEventFactory();
    }
    
    /**
     * Map a WordPress hook to a dedicated event class with the provided priority.
     *
     * @param  string  $wordpress_hook_name
     * @param  string  $map_to  The class name of the event that should be mapped
     * @param  int  $priority  The WordPress priority on which the mapping should happen.
     *
     * @throws InvalidArgumentException|LogicException
     */
    public function map(string $wordpress_hook_name, string $map_to, int $priority = 10)
    {
        $this->validate($wordpress_hook_name, $map_to);
        
        $this->mapValidated($wordpress_hook_name, $map_to, $priority);
    }
    
    /**
     * Map a WordPress hook to a dedicated event class which will ALWAYS be dispatched BEFORE
     * any other callbacks are run for the hook.
     *
     * @param  string  $wordpress_hook_name
     * @param  string  $map_to  The class name of the event that should be mapped
     *
     * @throws InvalidArgumentException|LogicException
     */
    public function mapFirst(string $wordpress_hook_name, string $map_to)
    {
        $this->validate($wordpress_hook_name, $map_to);
        
        $this->ensureFirst($wordpress_hook_name, $map_to);
    }
    
    /**
     * Map a WordPress hook to a dedicated event class which will ALWAYS be dispatched AFTER all
     * other callbacks are run for the hook.
     *
     * @param  string  $wordpress_hook_name
     * @param  string  $map_to  The class name of the event that should be mapped
     *
     * @throws InvalidArgumentException|LogicException
     */
    public function mapLast(string $wordpress_hook_name, string $map_to)
    {
        $this->validate($wordpress_hook_name, $map_to);
        $this->ensureLast($wordpress_hook_name, $map_to);
    }
    
    private function validate(string $wordpress_hook_name, string $map_to)
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
        
        if ( ! class_exists($map_to)) {
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
    
    private function mapValidated(string $wordpress_hook_name, string $map_to, int $priority)
    {
        if (isset($this->mapped_actions[$wordpress_hook_name][$map_to])) {
            add_action($wordpress_hook_name, $this->dispatchMappedAction($map_to), $priority, 9999);
        }
        else {
            add_filter($wordpress_hook_name, $this->dispatchMappedFilter($map_to), $priority, 9999);
        }
    }
    
    private function dispatchMappedAction(string $event_class) :Closure
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
            
            $this->event_dispatcher->dispatch($event);
        };
    }
    
    private function dispatchMappedFilter(string $event_class) :Closure
    {
        return function (...$args_from_wordpress_hooks) use ($event_class) {
            $event = $this->event_factory->make(
                $event_class,
                $args_from_wordpress_hooks
            );
            
            $payload = $this->event_dispatcher->dispatch($event);
            
            if ( ! $payload instanceof MappedFilter) {
                throw new LogicException(
                    "Mapped Filter [$event_class] has to return an instance of [MappedFilter]."
                );
            }
            
            return $payload->filterableAttribute();
        };
    }
    
    private function getWordPressHook(string $wordpress_hook_name) :?WP_Hook
    {
        return $GLOBALS['wp_filter'][$wordpress_hook_name] ?? null;
    }
    
    private function ensureFirst(string $wordpress_hook_name, string $map_to)
    {
        if (current_filter() === $wordpress_hook_name) {
            throw new LogicException(
                "You can can't map the event [$map_to] to the hook [$wordpress_hook_name] after it was fired."
            );
        }
        
        $wp_hook = $this->getWordPressHook($wordpress_hook_name);
        
        // Unless there is another filter registered with the priority PHP_INT_MIN
        // all we have to do is add our mapped event at this priority.
        // Even if other callback were to be added later with the same priority they would still be run after ours.
        if ( ! $wp_hook || empty($wp_hook->callbacks)) {
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
        ksort($wp_hook->callbacks, SORT_NUMERIC);
    }
    
    private function ensureLast(string $wordpress_hook_name, string $map_to)
    {
        add_action($wordpress_hook_name, function (...$args) use ($map_to) {
            // Even if somebody else registered a filter with PHP_INT_MAX our mapped action
            // will be run after the present callback unless it was also added during runtime
            // at the priority PHP_INT_MAX -1 which is highly unlikely.
            $this->mapValidated(current_filter(), $map_to, PHP_INT_MAX);
            return $args[0];
        }, PHP_INT_MAX - 1, 999);
    }
    
}