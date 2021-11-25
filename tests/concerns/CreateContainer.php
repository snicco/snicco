<?php

declare(strict_types=1);

namespace Tests\concerns;

use Snicco\Shared\ContainerAdapter;
use Snicco\Illuminate\IlluminateContainerAdapter;

/**
 * @internal
 */
trait CreateContainer
{
    
    public function createContainer() :ContainerAdapter
    {
        return new IlluminateContainerAdapter();
    }
    
}