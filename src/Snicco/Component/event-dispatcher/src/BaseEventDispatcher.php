<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

use Closure;
use InvalidArgumentException;
use Psr\EventDispatcher\StoppableEventInterface as StoppablePsrEvent;
use ReflectionClass;
use Snicco\Component\EventDispatcher\Exception\CantRemoveListener;
use Snicco\Component\EventDispatcher\Exception\InvalidListener;
use Snicco\Component\EventDispatcher\ListenerFactory\ListenerFactory;
use Snicco\Component\EventDispatcher\ListenerFactory\NewableListenerFactory;

use function array_merge;
use function call_user_func;
use function call_user_func_array;
use function class_exists;
use function class_implements;
use function get_parent_class;
use function in_array;
use function is_array;
use function is_string;
use function method_exists;
use function sprintf;

final class BaseEventDispatcher implements EventDispatcher
{
    private ListenerFactory $listener_factory;

    /**
     * @var array<string, array<string, array{0:class-string, 1:string}|Closure>>
     */
    private array $listeners = [];

    /**
     * @var array<string, array<array{0:class-string, 1:string}|Closure>>
     */
    private array $listener_cache = [];

    public function __construct(?ListenerFactory $listener_factory = null)
    {
        $this->listener_factory = $listener_factory ?: new NewableListenerFactory();
    }

    public function dispatch(object $event): object
    {
        $original_event = $event;

        $event = $this->transform($original_event);

        foreach ($this->getListenersForEvent($event->name()) as $listener) {
            if ($original_event instanceof StoppablePsrEvent && $original_event->isPropagationStopped()) {
                break;
            }
            $this->callListener(
                $listener,
                $event,
            );
        }

        return $original_event;
    }

    public function remove(string $event_name, $listener = null): void
    {
        $this->resetListenerCache($event_name);

        if (null === $listener) {
            unset($this->listeners[$event_name]);

            return;
        }

        if (! isset($this->listeners[$event_name])) {
            return;
        }

        $id = $this->parseListenerId($this->validatedListener($listener));

        if (! isset($this->listeners[$event_name][$id])) {
            return;
        }

        $listener = $this->listeners[$event_name][$id];

        if (! $listener instanceof Closure && in_array(Unremovable::class, (array) class_implements($listener[0]), true)) {
            throw CantRemoveListener::thatIsMarkedAsUnremovable(
                $listener,
                $event_name
            );
        }

        unset($this->listeners[$event_name][$id]);
    }

    public function subscribe(string $event_subscriber): void
    {
        if (! in_array(
            EventSubscriber::class,
            (array) class_implements($event_subscriber),
            true
        )) {
            throw new InvalidArgumentException(
                sprintf(
                    '[%s] does not implement [%s].',
                    $event_subscriber,
                    EventSubscriber::class
                )
            );
        }

        /** @var array<string,string> $events */
        $events = call_user_func([$event_subscriber, 'subscribedEvents']);

        foreach ($events as $name => $method) {
            $this->listen($name, [$event_subscriber, $method]);
        }
    }

    public function listen($event_name, $listener = null): void
    {
        if ($event_name instanceof Closure) {
            $this->listen(ClosureTypeHint::first($event_name), $event_name);

            return;
        }
        if (null === $listener) {
            throw new InvalidArgumentException('$listener can not be null if first $event_name is not a closure.');
        }

        $this->resetListenerCache($event_name);

        $listener = $this->validatedListener($listener);

        $id = $this->parseListenerId($listener);

        $this->listeners[$event_name][$id] = $listener;
    }

    private function transform(object $event): Event
    {
        if ($event instanceof Event) {
            return $event;
        }

        return GenericEvent::fromObject($event);
    }

    /**
     * @return array<array{0: class-string, 1:string}|Closure>
     */
    private function getListenersForEvent(string $event_name, bool $include_reflection = true): array
    {
        if (isset($this->listener_cache[$event_name])) {
            return $this->listener_cache[$event_name];
        }
        $listeners = $this->listeners[$event_name] ?? [];

        /** @var array<array{0: class-string, 1:string}|Closure> $listeners */
        $listeners = $include_reflection
                ? $this->mergeReflectionListeners($event_name, $listeners)
                : $listeners;

        $this->listener_cache[$event_name] = $listeners;

        return $listeners;
    }

    private function mergeReflectionListeners(string $event_name, array $listeners): array
    {
        if (! class_exists($event_name)) {
            return $listeners;
        }

        $interfaces = class_implements($event_name);
        $interfaces = (false === $interfaces)
            // @codeCoverageIgnoreStart
            ? []
            // @codeCoverageIgnoreEnd
            : $interfaces;

        foreach ($interfaces as $interface) {
            $listeners = array_merge($listeners, $this->getListenersForEvent($interface, false));
        }

        $parent = get_parent_class($event_name);

        if ($parent && (new ReflectionClass($parent))->isAbstract()) {
            $listeners = array_merge($listeners, $this->getListenersForEvent($parent, false));
        }

        return $listeners;
    }

    /**
     * @param array{0:class-string, 1:string}|Closure $listener
     *
     * @psalm-suppress MixedAssignment
     */
    private function callListener($listener, Event $event): void
    {
        $payload = $event->payload();
        $payload = is_array($payload) ? $payload : [$payload];

        if ($listener instanceof Closure) {
            $listener(...$payload);

            return;
        }

        $instance = $this->listener_factory->create(
            $listener[0],
            $event->name()
        );

        call_user_func_array([$instance, $listener[1]], $payload);
    }

    private function resetListenerCache(string $event_name): void
    {
        unset($this->listener_cache[$event_name]);
    }

    /**
     * @param array{0:class-string, 1:string}|Closure $validated_listener
     */
    private function parseListenerId($validated_listener): string
    {
        if ($validated_listener instanceof Closure) {
            return spl_object_hash($validated_listener);
        }

        return implode('::', $validated_listener);
    }

    /**
     * @param array{0:class-string, 1:string}|class-string|Closure $listener
     *
     * @return array{0: class-string, 1:string}|Closure
     *
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress MixedArgument
     */
    private function validatedListener($listener)
    {
        if ($listener instanceof Closure) {
            return $listener;
        }

        if (is_string($listener)) {
            if (! class_exists($listener)) {
                throw InvalidListener::becauseListenerClassDoesntExist($listener);
            }

            $invokable = method_exists($listener, '__invoke');

            if (! $invokable) {
                throw InvalidListener::becauseListenerCantBeInvoked($listener);
            }

            return [$listener, '__invoke'];
        }

        if (! is_array($listener)) {
            throw new InvalidListener('Listeners must be a string, array or closure.');
        }

        if (! class_exists($listener[0])) {
            throw InvalidListener::becauseListenerClassDoesntExist($listener[0]);
        }

        if (! method_exists($listener[0], $listener[1])) {
            throw InvalidListener::becauseProvidedClassMethodDoesntExist($listener);
        }

        return $listener;
    }
}
