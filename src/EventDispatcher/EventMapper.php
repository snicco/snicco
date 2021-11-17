<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Closure;
use LogicException;
use InvalidArgumentException;
use Snicco\EventDispatcher\Contracts\MappedFilter;
use Snicco\EventDispatcher\Contracts\MappedAction;

/**
 * @api
 */
final class EventMapper
{
    
    private EventDispatcher $event_dispatcher;
    
    private array $mapped_actions = [];
    private array $mapped_filters = [];
    
    public function __construct(EventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }
    
    public function map(string $wordpress_hook_name, string $map_to, int $priority = 10)
    {
        $this->validate($wordpress_hook_name, $map_to);
        
        if (isset($this->mapped_actions[$wordpress_hook_name][$map_to])) {
            add_action($wordpress_hook_name, $this->dispatchMappedAction($map_to), $priority, 9999);
        }
        else {
            add_filter($wordpress_hook_name, $this->dispatchMappedFilter($map_to), $priority, 9999);
        }
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
    
    private function dispatchMappedAction(string $event_name) :Closure
    {
        return function (...$args_from_wordpress_hooks) use ($event_name) {
            // Remove the empty "" that WordPress will pass for actions without any passed arguments.
            if (is_string($args_from_wordpress_hooks[0])
                && empty($args_from_wordpress_hooks[0])
                && count($args_from_wordpress_hooks) === 1) {
                array_shift($args_from_wordpress_hooks);
            }
            
            $event = new $event_name(...$args_from_wordpress_hooks);
            
            $this->event_dispatcher->dispatch($event);
        };
    }
    
    private function dispatchMappedFilter(string $event_name) :Closure
    {
        return function (...$args_from_wordpress_hooks) use ($event_name) {
            $event = new $event_name(...$args_from_wordpress_hooks);
            
            $payload = $this->event_dispatcher->dispatch($event);
            
            if ( ! $payload instanceof MappedFilter) {
                throw new LogicException(
                    "Mapped Filter [$event_name] has to return an instance of [MappedFilter]."
                );
            }
            
            return $payload->filterableAttribute();
        };
    }
    
}