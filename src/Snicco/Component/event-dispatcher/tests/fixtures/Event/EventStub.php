<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;

class EventStub implements Event
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $val1;
    public $val2;
    
    public function __construct($foo, $bar)
    {
        $this->val1 = $foo;
        $this->val2 = $bar;
    }
    
}