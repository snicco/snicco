<?php

declare(strict_types=1);

namespace Snicco\Bridge\Pimple\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Core\DIContainer;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Core\Testing\DIContainerContractTest;

final class PimpleContainerAdapterTest extends TestCase
{
    
    use DIContainerContractTest;
    
    function createContainer() :DIContainer
    {
        return new PimpleContainerAdapter();
    }
    
}