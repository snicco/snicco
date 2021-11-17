<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\CustomizablePayload;

/**
 * @internal
 */
final class GenericEvent implements Event, CustomizablePayload
{
    
    private array $arguments;
    
    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }
    
    public function payload() :array
    {
        return $this->arguments;
    }
    
}