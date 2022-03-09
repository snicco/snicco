<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Listener;

use Snicco\Component\EventDispatcher\Tests\fixtures\AssertListenerResponse;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event\FooEvent;

class ClassListener2
{
    use AssertListenerResponse;

    public function __invoke(FooEvent $event)
    {
        $this->respondedToEvent(FooEvent::class, ClassListener2::class, $event->val);
    }
}
