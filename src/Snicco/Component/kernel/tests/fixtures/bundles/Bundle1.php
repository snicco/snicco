<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\fixtures\bundles;

use RuntimeException;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

final class Bundle1 implements Bundle
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
        return $this->alias ?? 'bundle1';
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        if (! $kernel->usesBundle('bundle2')) {
            throw new RuntimeException('The knowledge about bundle2 should be available in configure()');
        }

        if ($config->has('bundle2.configured')) {
            throw new RuntimeException('bundle 2 configured first');
        }

        $config->set('bundle1.configured', true);
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();

        if (isset($container[Bundle2::class])) {
            throw new RuntimeException('bundle 2 registered first');
        }

        if (! $kernel->config()->get('bundle2.configured')) {
            throw new RuntimeException('bundle1 was registered before bundle2 was configured.');
        }

        $instance = new self();
        $instance->registered = true;

        $container[self::class] = $instance;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $container = $kernel->container();

        if ($container[Bundle2::class]->booted) {
            throw new RuntimeException('bundle 2 booted first');
        }

        if (! $container[Bundle2::class]->registered) {
            throw new RuntimeException('bundle1 was booted before bundle2 was registered.');
        }

        $container[Bundle1::class]->booted = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }
}
