<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\fixtures;

use Snicco\EventDispatcher\Contracts\Event;

abstract class AbstractLogin implements Event
{
    
    abstract public function message();
    
}