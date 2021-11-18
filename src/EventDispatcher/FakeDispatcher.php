<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Closure;
use ReflectionException;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\EventDispatcher\Contracts\EventParser;
use Snicco\EventDispatcher\Implementations\GenericEventParser;

use function Snicco\EventDispatcher\functions\getTypeHintedEventFromClosure;
use function Snicco\EventDispatcher\functions\wildcardPatternMatchesEventName;

final class FakeDispatcher implements Dispatcher
{
    
    private Dispatcher $dispatcher;
    
    /**
     * @var string[]
     */
    private array $events_to_fake = [];
    
    private array $dispatched_events = [];
    
    private bool $fake_all = false;
    
    private array       $dont_fake = [];
    private EventParser $event_parser;
    
    public function __construct(Dispatcher $dispatcher, ?EventParser $event_parser = null)
    {
        $this->dispatcher = $dispatcher;
        $this->event_parser = $event_parser ?? new GenericEventParser();
    }
    
    public function listen($event_name, $listener = null, bool $can_be_removed = true)
    {
        $this->dispatcher->listen($event_name, $listener, $can_be_removed);
    }
    
    public function dispatch($event, ...$payload) :Event
    {
        [$_event_name, $_event] = $this->event_parser->transformEventNameAndPayload(
            $event,
            $payload
        );
        
        if ($this->shouldFakeEvent($_event_name)) {
            return $_event;
        }
        
        $this->dispatched_events[$this->getEventName($_event_name)][] = [$_event];
        // Make sure to pass the arguments unchanged to the dispatcher.
        return $this->dispatcher->dispatch($event, ...$payload);
    }
    
    public function remove(string $event_name, string $listener_class = null)
    {
        $this->dispatcher->remove($event_name, $listener_class);
    }
    
    /**
     * @param  string|string[]  $event_names
     */
    public function fake($event_names = [])
    {
        $event_names = is_array($event_names) ? $event_names : [$event_names];
        
        if (empty($event_names)) {
            $this->fakeAll();
        }
        
        $this->events_to_fake = array_merge($this->events_to_fake, $event_names);
    }
    
    /**
     * @param  string|string[]  $event_names
     */
    public function fakeExcept($event_names = [])
    {
        $event_names = is_array($event_names) ? $event_names : [$event_names];
        
        if (empty($event_names)) {
            $this->fakeAll();
        }
        
        $this->dont_fake = array_merge($this->dont_fake, $event_names);
    }
    
    public function fakeAll()
    {
        $this->fake_all = true;
    }
    
    public function assertNotingDispatched()
    {
        $count = count($this->dispatched_events);
        PHPUnit::assertSame(0, $count, "$count event[s] dispatched.");
    }
    
    /**
     * @param  string|Closure  $event_name
     * @param  null  $condition  ;
     *
     * @throws ReflectionException
     */
    public function assertDispatched($event_name, $condition = null)
    {
        if ($event_name instanceof Closure) {
            $condition = $event_name;
            $event_name = getTypeHintedEventFromClosure($event_name);
        }
        
        PHPUnit::assertArrayHasKey(
            $event_name,
            $this->dispatched_events,
            "The event [$event_name] was not dispatched."
        );
        
        if ($condition instanceof Closure) {
            PHPUnit::assertNotEmpty(
                $this->getDispatched(
                    $event_name,
                    $condition
                ),
                "The event [$event_name] was dispatched but the provided condition did not pass."
            );
        }
    }
    
    public function assertDispatchedTimes(string $event_name, int $times = 1)
    {
        $count = count($this->getDispatched($event_name));
        
        PHPUnit::assertSame(
            $times,
            $count,
            "The event [$event_name] was dispatched [$count] time[s]."
        );
    }
    
    /**
     * @param  string|Closure  $event_name
     * @param  Closure|null  $condition
     *
     * @throws ReflectionException
     */
    public function assertNotDispatched($event_name, ?Closure $condition = null)
    {
        if ($event_name instanceof Closure) {
            $this->assertNotDispatched(getTypeHintedEventFromClosure($event_name), $event_name);
            return;
        }
        
        if ( ! $condition) {
            $this->assertDispatchedTimes($event_name, 0);
        }
        else {
            PHPUnit::assertCount(
                0,
                $this->getDispatched($event_name, $condition),
                "The event [$event_name] was dispatched and the condition passed."
            );
        }
    }
    
    private function shouldFakeEvent(string $event_name) :bool
    {
        if ($this->fake_all) {
            return true;
        }
        
        if (count($this->dont_fake)) {
            return ! in_array($event_name, $this->dont_fake, true);
        }
        
        $event_name = $this->getEventName($event_name);
        
        if (in_array($event_name, $this->events_to_fake, true)) {
            return true;
        }
        
        foreach ($this->events_to_fake as $name) {
            if (wildcardPatternMatchesEventName($name, $event_name)) {
                $this->fake($event_name);
                return true;
            }
        }
        
        return false;
    }
    
    private function getEventName($event) :string
    {
        return is_string($event) ? $event : get_class($event);
    }
    
    private function getDispatched(string $event_name, Closure $callback_condition = null) :array
    {
        $callback_condition = $callback_condition ?? fn() => true;
        
        $passed = [];
        
        foreach ($this->dispatched_events[$event_name] ?? [] as $events) {
            foreach ($events as $event) {
                if (call_user_func($callback_condition, $event, $event_name) === true) {
                    $passed[] = $event;
                }
            }
        }
        
        return $passed;
    }
    
}