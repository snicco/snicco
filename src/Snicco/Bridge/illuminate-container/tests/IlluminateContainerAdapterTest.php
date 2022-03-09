<?php

declare(strict_types=1);

namespace Snicco\Bridge\IlluminateContainer\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Bridge\IlluminateContainer\IlluminateContainerAdapter;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Testing\DIContainerContractTest;

final class IlluminateContainerAdapterTest extends TestCase
{
    use DIContainerContractTest;

    public function createContainer(): DIContainer
    {
        return new IlluminateContainerAdapter();
    }
}
