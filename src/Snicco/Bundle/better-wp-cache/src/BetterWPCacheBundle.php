<?php

declare(strict_types=1);


namespace Snicco\Bundle\BetterWPCache;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Snicco\Bundle\BetterWPCache\Option\BetterWPCacheOption;
use Snicco\Component\BetterWPCache\CacheFactory;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function array_replace;
use function copy;
use function dirname;
use function is_file;

final class BetterWPCacheBundle implements Bundle
{
    public const ALIAS = 'sniccowp/better-wp-cache-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $defaults = require dirname(__DIR__) . '/config/better-wp-cache.php';
        $config->set('better-wp-cache', array_replace($defaults, $config->getArray('better-wp-cache', [])));

        $this->copyConfiguration($kernel);
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container()->shared(CacheItemPoolInterface::class, function () use ($kernel) {
            /**
             * @var non-empty-string $group
             */
            $group = $kernel->config()->getString('better-wp-cache.' . BetterWPCacheOption::CACHE_GROUP);
            return CacheFactory::psr6($group);
        });
        $kernel->container()->shared(CacheInterface::class, function () use ($kernel) {
            /**
             * @var non-empty-string $group
             */
            $group = $kernel->config()->getString('better-wp-cache.' . BetterWPCacheOption::CACHE_GROUP);
            return CacheFactory::psr16($group);
        });
        $kernel->container()->shared(TaggableCacheItemPoolInterface::class, function () use ($kernel) {
            return CacheFactory::taggable($kernel->container()->make(CacheItemPoolInterface::class));
        });
    }

    public function bootstrap(Kernel $kernel): void
    {
        //
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    private function copyConfiguration(Kernel $kernel): void
    {
        if (! $kernel->env()->isDevelop()) {
            return;
        }
        $destination = $kernel->directories()->configDir() . '/better-wp-cache.php';
        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/better-wp-cache.php', $destination);

        if (false === $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Could not copy the default templating config to destination [$destination]");
            // @codeCoverageIgnoreEnd
        }
    }
}
