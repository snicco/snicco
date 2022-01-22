<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures;

use Snicco\Component\EventDispatcher\Contracts\Event;

interface LoggableEvent extends Event
{
    
    public function message();
    
}