<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

final class EventStub implements Event
{
    use ClassAsName;
    use ClassAsPayload;

    public string $val1;

    public string $val2;

    public function __construct(string $foo, string $bar)
    {
        $this->val1 = $foo;
        $this->val2 = $bar;
    }
}
