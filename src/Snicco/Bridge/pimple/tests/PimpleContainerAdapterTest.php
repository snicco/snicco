<?php

declare(strict_types=1);

namespace Snicco\Bridge\Pimple\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\DIContainerTest\DIContainerContractTests;
use Snicco\Component\Kernel\DIContainer;

/**
 * @internal
 */
final class PimpleContainerAdapterTest extends TestCase
{
    use DIContainerContractTests;

    public function createContainer(): DIContainer
    {
        return new PimpleContainerAdapter();
    }
}
