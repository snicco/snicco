<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\fixtures\bundles;

use RuntimeException;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

final class BundleAssertsMethodOrder implements Bundle
{
    public bool $registered = false;

    public bool $booted = false;

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->set($this->alias() . '.configured', true);
    }

    public function alias(): string
    {
        return 'bundle_that_asserts_order';
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();

        if (! $kernel->config()->get($this->alias() . '.configured')) {
            throw new RuntimeException('register was called before configure.');
        }

        $instance = new self();
        $instance->registered = true;

        $container[self::class] = $instance;
    }

    public function bootstrap(Kernel $kernel): void
    {
        if (! $kernel->config()->get($this->alias() . '.configured')) {
            throw new RuntimeException('bootstrap was called before configure.');
        }

        $container = $kernel->container();

        if (! isset($container[self::class])) {
            throw new RuntimeException('bootstrap was called before register.');
        }

        $container[self::class]->booted = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }
}
