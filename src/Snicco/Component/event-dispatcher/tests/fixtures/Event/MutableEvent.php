<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\BetterWPHooks\Mutable;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;

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