<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\ObjectCopier;

/**
 * @internal
 */
final class NativeObjetCopier implements ObjectCopier
{
    
    public function copy(Event $object) :Event
    {
        return clone $object;
    }
    
}