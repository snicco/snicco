<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

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