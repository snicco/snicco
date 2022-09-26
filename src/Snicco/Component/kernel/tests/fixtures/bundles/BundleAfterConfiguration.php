<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\fixtures\bundles;

use RuntimeException;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

final class BundleAfterConfiguration implements Bundle
{
    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->set(BundleAfterConfiguration::class, 'did-not-work');

        $kernel->afterConfiguration(function (WritableConfig $config) {
            if (! $config->has(BundleAfterConfiguration::class)) {
                throw new RuntimeException('afterConfiguration must be called after all bundles are configured.');
            }
            $config->set(BundleAfterConfiguration::class, 'it-worked');
        });
    }

    public function register(Kernel $kernel): void
    {
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return self::class;
    }
}
