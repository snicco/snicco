<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Tests\fixtures\Event;

class LogEvent2 implements Event\LoggableEvent
{
    use ClassAsName;
    use ClassAsPayload;

    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function message()
    {
        return $this->message;
    }
}
