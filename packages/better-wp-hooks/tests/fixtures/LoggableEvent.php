<?php

declare(strict_types=1);

namespace Tests\BetterWPHooks\fixtures;

use Snicco\EventDispatcher\Contracts\Event;

interface LoggableEvent extends Event
{
    
    public function message();
    
}