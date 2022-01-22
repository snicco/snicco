<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures;

use Snicco\Component\EventDispatcher\Contracts\Event;

abstract class AbstractLogin implements Event
{
    
    abstract public function message();
    
}