<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\fixtures;

use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\Mutable;

class MutableEvent implements Event, Mutable
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public $val;
    
    public function __construct($val)
    {
        $this->val = $val;
    }
    
}