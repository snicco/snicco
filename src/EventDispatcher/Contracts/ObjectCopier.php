<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

/**
 * @api
 */
interface ObjectCopier
{
    
    public function copy(Event $object) :Event;
    
}