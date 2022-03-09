<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Listener;

use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\FooEvent;
use Snicco\Component\EventDispatcher\Unremovable;

use function get_class;

final class UnremovableListener implements Unremovable
{
    use AssertListenerResponse;

    public function __invoke(FooEvent $event)
    {
        $this->respondedToEvent(FooEvent::class, self::class, $event->val);
    }

    public function customHandleMethod($event): void
    {
        $this->respondedToEvent(get_class($event), self::class, $event->val);
    }
}
