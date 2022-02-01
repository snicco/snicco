<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\helpers;

use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Core\DIContainer;

trait CreateTestContainer
{

    private function createContainer(): DIContainer
    {
        return new PimpleContainerAdapter();
    }

}