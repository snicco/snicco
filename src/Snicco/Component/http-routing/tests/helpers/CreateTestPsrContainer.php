<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\helpers;

use Snicco\Component\Core\DIContainer;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;

trait CreateTestPsrContainer
{
    
    public function createContainer() :DIContainer
    {
        return new PimpleContainerAdapter();
    }
    
}