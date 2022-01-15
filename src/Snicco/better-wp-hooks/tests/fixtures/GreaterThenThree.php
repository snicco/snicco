<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\fixtures;

use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\DispatchesConditionally;

class GreaterThenThree implements Event, DispatchesConditionally
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public int $val;
    
    public function __construct(int $val)
    {
        $this->val = $val;
    }
    
    public function shouldDispatch() :bool
    {
        return $this->val > 3;
    }
    
}