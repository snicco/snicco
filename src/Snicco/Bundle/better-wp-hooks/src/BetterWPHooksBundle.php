<?php

declare(strict_types=1);

namespace Snicco\Bundle\BetterWPHooks;

use Psr\EventDispatcher\EventDispatcherInterface;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\BetterWPHooks\WPHookAPI;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\ListenerFactory\PsrListenerFactory;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function class_exists;

final class BetterWPHooksBundle implements Bundle
{
    public const ALIAS = 'sniccowp/better-wp-hooks-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();

        $hook_api = new WPHookAPI();

        $container->shared(EventDispatcher::class, function () use (
            $kernel,
            $container,
            $hook_api
        ): EventDispatcherInterface {
            $listener_factory = new PsrListenerFactory($container);
            $dispatcher = new WPEventDispatcher(new BaseEventDispatcher($listener_factory), $hook_api);
            if ($kernel->env()->isTesting() && class_exists(TestableEventDispatcher::class)) {
                $dispatcher = new TestableEventDispatcher($dispatcher);
            }

            return $dispatcher;
        });
        $container->shared(
            EventDispatcherInterface::class,
            fn (): EventDispatcher => $container->make(EventDispatcher::class)
        );

        if ($kernel->env()->isTesting()) {
            $container->shared(
                TestableEventDispatcher::class,
                fn (): EventDispatcher => $container->make(EventDispatcher::class)
            );
        }

        $container->shared(
            EventMapper::class,
            fn (): EventMapper => new EventMapper($container->make(EventDispatcher::class), $hook_api)
        );
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return self::ALIAS;
    }
}
