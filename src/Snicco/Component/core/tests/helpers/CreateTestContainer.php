<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\helpers;

use Snicco\Component\Core\DIContainer;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;

trait CreateTestContainer
{
    
    final private function createContainer() :DIContainer
    {
        return new PimpleContainerAdapter();
    }
    
}