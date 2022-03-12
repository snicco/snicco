<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\wordpress\fixtures;

use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

final class Bootstrapper implements \Snicco\Component\Kernel\Bootstrapper
{
    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(
                WebTestCaseController::class,
                fn (): WebTestCaseController => new WebTestCaseController($kernel->container()->make(Mailer::class))
            );
    }

    public function bootstrap(Kernel $kernel): void
    {
    }
}
