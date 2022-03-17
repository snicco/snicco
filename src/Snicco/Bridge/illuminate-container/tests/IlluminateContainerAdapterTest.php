<?php

declare(strict_types=1);

namespace Snicco\Bridge\IlluminateContainer\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Bridge\IlluminateContainer\IlluminateContainerAdapter;
use Snicco\Component\DIContainerTest\DIContainerContractTests;
use Snicco\Component\Kernel\DIContainer;

/**
 * @internal
 */
final class IlluminateContainerAdapterTest extends TestCase
{
    use DIContainerContractTests;

    public function createContainer(): DIContainer
    {
        return new IlluminateContainerAdapter();
    }
}
