<?php

declare(strict_types=1);

namespace Test\Helpers;

use Snicco\Component\Core\DIContainer;
use Snicco\PimpleContainer\PimpleDIContainer;

/**
 * @internal
 */
trait CreateContainer
{
    
    public function createContainer() :DIContainer
    {
        return new PimpleDIContainer();
    }
    
}