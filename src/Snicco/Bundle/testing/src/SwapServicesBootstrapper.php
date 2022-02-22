<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing;

use Closure;
use Snicco\Component\Kernel\Bootstrapper;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

final class SwapServicesBootstrapper implements Bootstrapper
{

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        //
    }

    public function register(Kernel $kernel): void
    {
        /** @var array<string,object|Closure|scalar> $definitions */
        $definitions = $kernel->config()->getArray(self::class);

        foreach ($definitions as $key => $new_value) {
            $kernel->container()[$key] = $new_value;
        }
    }

    public function bootstrap(Kernel $kernel): void
    {
        //
    }
}