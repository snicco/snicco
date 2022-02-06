<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\helpers;

use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Kernel\DIContainer;

trait CreateTestPsrContainer
{

    public function createContainer(): DIContainer
    {
        return new PimpleContainerAdapter();
    }

}