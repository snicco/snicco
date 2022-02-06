<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\fixtures\bundles;

use RuntimeException;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use stdClass;

class Bundle2 implements Bundle
{

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
        if (!$config->has('bundle1.configured')) {
            throw new RuntimeException('bundle1 should have been configured first.');
        }
        $config->set('bundle2.configured', true);
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        if (!isset($container['bundle1.registered'])) {
            throw new RuntimeException('bundle1 should have been registered first');
        }
        $container['bundle2.registered'] = true;
        $std = new stdClass();
        $std->val = false;
        $container['bundle2.booted'] = $std;
    }

    public function bootstrap(Kernel $kernel): void
    {
        $container = $kernel->container();
        if (!isset($container['bundle1.booted'])) {
            throw new RuntimeException('bundle1 should have been booted first');
        }
        $container['bundle2.booted']->val = true;
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

}