<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

use Closure;
use ReflectionException;
use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;

use function count;
use function is_array;
use function Snicco\Component\EventDispatcher\functions\getTypeHintedObjectFromClosure;

/**
 * @api
 */
final class TestableEventDispatcher implements EventDispatcher
{
    
    private EventDispatcher $real_dispatcher;
    
    /**
     * @var string[]
     */
    private array $events_to_fake = [];
    
    /**
     * @var array<string,Event[]>
     */
    private $dispatched_events = [];
    
    /**
     * @var string[]
     */
    private array $dont_fake = [];
    
    private bool $fake_all = false;
    
    public function __construct(EventDispatcher $real_dispatcher)
    {
        $this->real_dispatcher = $real_dispatcher;
    }
    
    public function listen($event_name, $listener = null, bool $can_be_removed = true) :void
    {
        $this->real_dispatcher->listen($event_name, $listener, $can_be_removed);
    }
    
    public function dispatch(object $event) :object
    {
        $original = $event;
        $event = $event instanceof Event ? $event : GenericEvent::fromObject($event);
        
        $this->dispatched_events[$event->name()][] = [$event];
        
        if ($this->shouldFakeEvent($event->name())) {
            return $original;
        }
        
        $this->real_dispatcher->dispatch($event);
        
        return $original;
    }
    
    public function remove(string $event_name, $listener = null) :void
    {
        $this->real_dispatcher->remove($event_name, $listener);
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
        
        if ( ! count($event_names)) {
            throw new InvalidArgumentException('$event_names cant be an empty array.');
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
     * @param  null  $condition
     *
     * @throws ReflectionException
     */
    public function assertDispatched($event_name, $condition = null)
    {
        if ($event_name instanceof Closure) {
            $condition = $event_name;
            $event_name = getTypeHintedObjectFromClosure($event_name);
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
            $this->assertNotDispatched(getTypeHintedObjectFromClosure($event_name), $event_name);
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
    
    public function reset()
    {
        $this->events_to_fake = [];
        $this->fake_all = false;
        $this->dont_fake = [];
        $this->dispatched_events = [];
    }
    
    public function subscribe(string $event_subscriber) :void
    {
        $this->real_dispatcher->subscribe($event_subscriber);
    }
    
    private function shouldFakeEvent(string $event_name) :bool
    {
        if ($this->fake_all) {
            return true;
        }
        
        if (count($this->dont_fake)) {
            return ! in_array($event_name, $this->dont_fake, true);
        }
        
        if (in_array($event_name, $this->events_to_fake, true)) {
            return true;
        }
        
        return false;
    }
    
    private function getDispatched(string $event_name, Closure $callback_condition = null) :array
    {
        $callback_condition = $callback_condition ?? function () { return true; };
        
        $passed = [];
        
        foreach ($this->dispatched_events[$event_name] ?? [] as $events) {
            foreach ($events as $event) {
                /** @var Event $payload */
                $payload = $event->payload();
                
                $payload = is_array($payload) ? $payload : [$payload];
                
                if ($callback_condition(...$payload) === true) {
                    $passed[] = $event;
                }
            }
        }
        
        return $passed;
    }
    
}