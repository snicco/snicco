<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Listener;

use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\FooEvent;

class ClassListener
{

    use AssertListenerResponse;

    public function __invoke(FooEvent $event)
    {
        $this->respondedToEvent(FooEvent::class, ClassListener::class, $event->val);
    }

    public function customHandleMethod($event): void
    {
        $this->respondedToEvent(get_class($event), ClassListener::class, $event->val);
    }

}