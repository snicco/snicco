<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Core\Shared\ContainerAdapter;
use Snicco\PimpleContainer\PimpleContainerAdapter;

/**
 * @internal
 */
trait CreateContainer
{
    
    public function createContainer() :ContainerAdapter
    {
        return new PimpleContainerAdapter();
    }
    
}