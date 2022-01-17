<?php

declare(strict_types=1);

namespace Snicco\Bridge\IlluminateContainer\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Core\DIContainer;
use Snicco\Component\Core\Testing\DIContainerContractTest;
use Snicco\Bridge\IlluminateContainer\IlluminateContainerAdapter;

final class IlluminateContainerAdapterTest extends TestCase
{
    
    use DIContainerContractTest;
    
    function createContainer() :DIContainer
    {
        return new IlluminateContainerAdapter();
    }
    
}