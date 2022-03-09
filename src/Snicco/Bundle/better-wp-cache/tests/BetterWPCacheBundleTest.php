<?php

declare(strict_types=1);


namespace Snicco\Bundle\BetterWPCache\Tests;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Snicco\Bundle\BetterWPCache\BetterWPCacheBundle;
use Snicco\Bundle\BetterWPCache\Option\BetterWPCacheOption;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;
use function file_put_contents;
use function is_file;
use function var_export;

final class BetterWPCacheBundleTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_alias(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->boot();

        $this->assertTrue($kernel->usesBundle(BetterWPCacheBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_psr6_cache_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(CacheItemPoolInterface::class, $kernel);
    }

    /**
     * @test
     */
    public function test_psr16_cache_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(CacheInterface::class, $kernel);
    }

    /**
     * @test
     */
    public function test_taggable_cache_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(TaggableCacheItemPoolInterface::class, $kernel);
    }

    /**
     * @test
     */
    public function the_default_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $this->assertFalse(is_file($this->directories->configDir() . '/better-wp-cache.php'));

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/better-wp-cache.php'));

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $config = require $this->directories->configDir() . '/better-wp-cache.php';

        $this->assertSame(
            require dirname(__DIR__) . '/config/better-wp-cache.php',
            $config
        );
    }

    /**
     * @test
     */
    public function the_default_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        file_put_contents(
            $this->directories->configDir() . '/better-wp-cache.php',
            '<?php return ' . var_export([BetterWPCacheOption::CACHE_GROUP => ['foo']], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/better-wp-cache.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame(
            [BetterWPCacheOption::CACHE_GROUP => ['foo']],
            require $this->directories->configDir() . '/better-wp-cache.php'
        );
    }

    /**
     * @test
     */
    public function the_default_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );

        $this->assertFalse(is_file($this->directories->configDir() . '/better-wp-cache.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/better-wp-cache.php'));
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/fixtures';
    }
}
