<?php

declare(strict_types=1);

namespace Tests\concerns;

use Contracts\ContainerAdapter;
use SniccoAdapter\BaseContainerAdapter;

/**
 * @internal
 */
trait CreateContainer
{
    
    public function createContainer() :ContainerAdapter
    {
        return new BaseContainerAdapter();
    }
    
}