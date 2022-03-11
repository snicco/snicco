<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\helpers;

use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Kernel\DIContainer;

trait CreateTestContainer
{
    private function createContainer(): DIContainer
    {
        return new PimpleContainerAdapter();
    }
}
