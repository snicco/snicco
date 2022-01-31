<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\Event;

abstract class AbstractLogin implements Event
{
    
    abstract public function message();
    
}