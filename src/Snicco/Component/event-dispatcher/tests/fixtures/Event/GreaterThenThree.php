<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\BetterWPHooks\EventMapping\DispatchesConditionally;

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