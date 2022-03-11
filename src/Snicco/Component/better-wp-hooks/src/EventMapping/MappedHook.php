<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\EventMapping;

use Snicco\Component\EventDispatcher\Event;

interface MappedHook extends Event
{
    public function shouldDispatch(): bool;
}
