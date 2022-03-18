<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Testing;

use Closure;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Assert as PHPUnit;
use ReflectionException;
use Snicco\Component\EventDispatcher\ClosureTypeHint;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\GenericEvent;

use function call_user_func_array;
use function count;
use function in_array;
use function is_array;
use function is_bool;

final class TestableEventDispatcher implements EventDispatcher
{
    private EventDispatcher $real_dispatcher;

    /**
     * @var string[]
     */
    private array $events_to_fake = [];

    /**
     * @var array<string, list<Event>>
     */
    private array $dispatched_events = [];

    /**
     * @var string[]
     */
    private array $dont_fake = [];

    private bool $fake_all = false;

    public function __construct(EventDispatcher $real_dispatcher)
    {
        $this->real_dispatcher = $real_dispatcher;
    }

    public function listen($event_name, $listener = null): void
    {
        $this->real_dispatcher->listen($event_name, $listener);
    }

    public function dispatch(object $event): object
    {
        $original = $event;
        $event = $event instanceof Event ? $event : GenericEvent::fromObject($event);

        $this->dispatched_events[$event->name()][] = $event;

        if ($this->shouldFakeEvent($event->name())) {
            return $original;
        }

        $this->real_dispatcher->dispatch($event);

        return $original;
    }

    public function remove(string $event_name, $listener = null): void
    {
        $this->real_dispatcher->remove($event_name, $listener);
    }

    public function subscribe(string $event_subscriber): void
    {
        $this->real_dispatcher->subscribe($event_subscriber);
    }

    /**
     * @param string|string[] $event_names
     */
    public function fake($event_names = []): void
    {
        $event_names = is_array($event_names) ? $event_names : [$event_names];

        if (empty($event_names)) {
            $this->fakeAll();
        }

        $this->events_to_fake = array_merge($this->events_to_fake, $event_names);
    }

    public function fakeAll(): void
    {
        $this->fake_all = true;
    }

    /**
     * @param string|string[] $event_names
     */
    public function fakeExcept($event_names = []): void
    {
        $event_names = is_array($event_names) ? $event_names : [$event_names];

        if ([] === $event_names) {
            throw new InvalidArgumentException('$event_names cant be an empty array.');
        }

        $this->dont_fake = array_merge($this->dont_fake, $event_names);
    }

    public function assertNotingDispatched(): void
    {
        $count = count($this->dispatched_events);
        PHPUnit::assertSame(0, $count, sprintf('%d event[s] dispatched.', $count));
    }

    /**
     * @param string|Closure(mixed):bool $event_name
     * @param null|Closure(mixed...):bool $condition
     *
     * @throws ReflectionException
     */
    public function assertDispatched($event_name, $condition = null): void
    {
        if ($event_name instanceof Closure) {
            $condition = $event_name;
            $event_name = ClosureTypeHint::first($event_name);
        }

        PHPUnit::assertArrayHasKey(
            $event_name,
            $this->dispatched_events,
            sprintf('The event [%s] was not dispatched.', $event_name)
        );

        if ($condition instanceof Closure) {
            PHPUnit::assertNotEmpty(
                $this->getDispatched($event_name, $condition),
                sprintf('The event [%s] was dispatched but the provided condition did not pass.', $event_name)
            );
        }
    }

    /**
     * @param string|Closure(mixed):bool $event_name
     * @param null|Closure(mixed...):bool $condition
     *
     * @throws ReflectionException
     */
    public function assertNotDispatched($event_name, ?Closure $condition = null): void
    {
        if ($event_name instanceof Closure) {
            $this->assertNotDispatched(ClosureTypeHint::first($event_name), $event_name);

            return;
        }

        if (null === $condition) {
            $this->assertDispatchedTimes($event_name, 0);
        } else {
            PHPUnit::assertCount(
                0,
                $this->getDispatched($event_name, $condition),
                sprintf('The event [%s] was dispatched and the condition passed.', $event_name)
            );
        }
    }

    public function assertDispatchedTimes(string $event_name, int $times = 1): void
    {
        $count = count($this->getDispatched($event_name));

        PHPUnit::assertSame(
            $times,
            $count,
            sprintf('The event [%s] was dispatched [%d] time[s].', $event_name, $count)
        );
    }

    public function resetDispatchedEvents(): void
    {
        $this->events_to_fake = [];
        $this->fake_all = false;
        $this->dont_fake = [];
        $this->dispatched_events = [];
    }

    private function shouldFakeEvent(string $event_name): bool
    {
        if ($this->fake_all) {
            return true;
        }

        if ([] !== $this->dont_fake) {
            return ! in_array($event_name, $this->dont_fake, true);
        }

        return in_array($event_name, $this->events_to_fake, true);
    }

    /**
     * @return Event[]
     */
    private function getDispatched(string $event_name, Closure $callback_condition = null): array
    {
        $passed = [];

        foreach ($this->dispatched_events[$event_name] ?? [] as $event) {
            if (null === $callback_condition) {
                $passed[] = $event;

                continue;
            }

            /** @var mixed $payload */
            $payload = $event->payload();

            $payload = is_array($payload) ? $payload : [$payload];

            $res = call_user_func_array($callback_condition, $payload);

            if (! is_bool($res)) {
                throw new LogicException('Test closure that asserts events did not return boolean.');
            }

            if ($res) {
                $passed[] = $event;
            }
        }

        return $passed;
    }
}
