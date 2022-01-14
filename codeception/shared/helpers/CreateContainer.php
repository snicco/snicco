<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Core\DIContainer;
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