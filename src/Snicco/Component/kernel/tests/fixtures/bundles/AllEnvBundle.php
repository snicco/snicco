<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\fixtures\bundles;

use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

final class AllEnvBundle implements Bundle
{
    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return 'bundle_always';
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }
}
