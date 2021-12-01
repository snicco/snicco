<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Events;

use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\IsForbiddenToWordPress;

abstract class CoreEvent implements Event, IsForbiddenToWordPress
{
    
    use ClassAsPayload;
    use ClassAsName;
}