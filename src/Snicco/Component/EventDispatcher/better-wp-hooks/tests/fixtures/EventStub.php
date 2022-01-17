<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\fixtures;

use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;
use Snicco\EventDispatcher\Contracts\Event;

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