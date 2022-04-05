<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks;

use Psr\EventDispatcher\StoppableEventInterface as PsrStoppableEvent;
use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\GenericEvent;

final class WPEventDispatcher implements EventDispatcher
{
    private EventDispatcher $dispatcher;

    private WPHookAPI $wp;

    public function __construct(EventDispatcher $dispatcher, WPHookAPI $wp = null)
    {
        $this->dispatcher = $dispatcher;
        $this->wp = $wp ?: new WPHookAPI();
    }

    public static function fromDefaults(): self
    {
        return new self(new BaseEventDispatcher());
    }

    public function dispatch(object $event): object
    {
        $original = $event;

        /** @var Event $event */
        $event = $original instanceof Event
            ? $original
            : GenericEvent::fromObject($original);

        $this->dispatcher->dispatch($event);

        // This event should not be shared with wp. Sharing event messages is opt-in not opt-out.
        if (! $original instanceof ExposeToWP) {
            return $original;
        }

        if ($original instanceof PsrStoppableEvent && $original->isPropagationStopped()) {
            return $original;
        }

        /*
         * The original object is sent through the WP Hook system and can be "enhanced" if
         * that is desired. We never want to send primitive values in order to have type-safety in the
         * calling code. We also don't return the result of the call to wp because we have no control
         * over what third-party devs return there. In order to comply with psr14 we always need to
         * return the original dispatched object.
         */
        $this->wp->doAction($event->name(), $original);

        return $original;
    }

    public function remove(string $event_name, $listener = null): void
    {
        $this->dispatcher->remove($event_name, $listener);
    }

    public function listen($event_name, $listener = null): void
    {
        $this->dispatcher->listen($event_name, $listener);
    }

    public function subscribe(string $event_subscriber): void
    {
        $this->dispatcher->subscribe($event_subscriber);
    }
}
