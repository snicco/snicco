<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Implementations;

use Snicco\Component\EventDispatcher\Contracts\Event;
use Snicco\Component\EventDispatcher\Contracts\ObjectCopier;

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