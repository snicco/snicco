<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

class FilterableEvent implements Event
{

    use ClassAsName;
    use ClassAsPayload;

    public $val;

    public function __construct($val)
    {
        $this->val = $val;
    }

}