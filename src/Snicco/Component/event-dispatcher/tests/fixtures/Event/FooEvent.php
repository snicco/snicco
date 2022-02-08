<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

class FooEvent implements Event
{

    use ClassAsName;
    use ClassAsPayload;

    public string $val;

    public function __construct(string $val)
    {
        $this->val = $val;
    }

}