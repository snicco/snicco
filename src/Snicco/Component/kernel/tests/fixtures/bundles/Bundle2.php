<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\fixtures\bundles;

use RuntimeException;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

final class Bundle2 implements Bundle
{
    public bool $registered = false;

    public bool $booted = false;

    private ?string $alias;

    public function __construct(string $alias = null)
    {
        $this->alias = $alias;
    }

    public function alias(): string
    {
        return $this->alias ?? 'bundle2';
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        if (! $config->has('bundle1.configured')) {
            throw new RuntimeException('bundle1 should have been configured first.');
        }

        $config->set('bundle2.configured', true);
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        if (! isset($container[Bundle1::class])) {
            throw new RuntimeException('bundle1 should have been registered first');
        }

        $instance = new self();
        $instance->registered = true;

        $container[self::class] = $instance;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $container = $kernel->container();
        if (! $container[Bundle1::class]->booted) {
            throw new RuntimeException('bundle1 should have been booted first');
        }

        $container[self::class]->booted = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }
}
