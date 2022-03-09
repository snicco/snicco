<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;

final class PsrEventDispatcherTest extends TestCase
{
    use AssertListenerResponse;

    /**
     * @test
     */
    public function objects_are_dispatched_as_mutable_classes(): void
    {
        $psr_dispatcher = new BaseEventDispatcher();

        $psr_dispatcher->listen(Object1::class, function (Object1 $event) {
            $event->foo = 'FOO';
        });

        $psr_dispatcher->listen(Object1::class, function (Object1 $event) {
            $this->assertSame('FOO', $event->foo);
            $event->bar = 'BAR';
        });

        $event = new Object1();
        $returned = $psr_dispatcher->dispatch($event);

        $this->assertSame('FOOBAR', $event->foo . $event->bar);
        $this->assertSame($event, $returned);
    }

    /**
     * @test
     */
    public function event_propagation_will_be_stopped_if_the_psr_interface_is_implemented(): void
    {
        $psr_dispatcher = new BaseEventDispatcher();

        $psr_dispatcher->listen(StoppableEvent::class, function (StoppableEvent $event) {
            $this->assertSame(1, $event->count);
            $event->incrementCount();
        });

        $psr_dispatcher->listen(StoppableEvent::class, function (StoppableEvent $event) {
            $this->assertSame(2, $event->count);
            $event->incrementCount();
            $event->should_stop = true;
        });

        $psr_dispatcher->listen(StoppableEvent::class, function (StoppableEvent $event) {
            $this->fail('This should not have been called.');
        });

        $event = new StoppableEvent();
        $this->assertSame(1, $event->count);

        $returned = $psr_dispatcher->dispatch($event);

        $this->assertSame(3, $returned->count);
        $this->assertSame($event, $returned);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetListenersResponses();
    }

    protected function tearDown(): void
    {
        $this->resetListenersResponses();
        parent::tearDown();
    }
}

class Object1
{
    public string $foo = 'foo';

    public string $bar = 'foo';
}

class StoppableEvent implements StoppableEventInterface
{
    public bool $should_stop = false;

    public int $count = 1;

    public function isPropagationStopped(): bool
    {
        return $this->should_stop;
    }

    public function incrementCount(): void
    {
        $this->count++;
    }
}
