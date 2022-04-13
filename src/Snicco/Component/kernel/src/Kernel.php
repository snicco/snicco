<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel;

use Generator;
use LogicException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Snicco\Component\Kernel\Configuration\ConfigCache;
use Snicco\Component\Kernel\Configuration\ConfigLoader;
use Snicco\Component\Kernel\Configuration\NullCache;
use Snicco\Component\Kernel\Configuration\PHPFileCache;
use Snicco\Component\Kernel\Configuration\ReadOnlyConfig;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function array_merge;
use function get_class;
use function implode;
use function sprintf;

final class Kernel
{
    private DIContainer $container;

    private Environment $env;

    private Directories $dirs;

    private ConfigCache $config_cache;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private ReadOnlyConfig $read_only_config;

    private bool $booted = false;

    /**
     * @var array<string,Bundle>
     */
    private array $bundles = [];

    /**
     * @var Bootstrapper[]
     */
    private array $bootstrappers = [];

    private bool $loaded_from_cache = true;

    /**
     * @var array<callable(Kernel):void>
     */
    private array $after_register_callbacks = [];

    /**
     * @var array<callable(WritableConfig,Kernel):void>
     */
    private array $after_config_loaded_callbacks = [];

    public function __construct(
        DIContainer $container,
        Environment $env,
        Directories $dirs,
        ?ConfigCache $config_cache = null
    ) {
        $this->container = $container;
        $this->env = $env;
        $this->dirs = $dirs;
        $this->config_cache = $config_cache ?: $this->determineCache($this->env);
        $this->container[ContainerInterface::class] = $this->container;
    }

    public function boot(): void
    {
        if ($this->booted) {
            throw new LogicException('The kernel cant be booted twice.');
        }

        $cached_config = $this->config_cache->get($this->configCacheFile(), fn (): array => $this->loadConfiguration());

        $this->read_only_config = ReadOnlyConfig::fromArray($cached_config);

        if ($this->loaded_from_cache) {
            $this->setBundlesAndBootstrappers(
                $this->read_only_config->getArray('kernel.bundles'),
                $this->read_only_config->getListOfStrings('kernel.bootstrappers')
            );
        }

        $this->registerBundles();

        $this->container->lock();

        $this->bootBundles();

        $this->booted = true;
    }

    public function booted(): bool
    {
        return $this->booted;
    }

    public function env(): Environment
    {
        return $this->env;
    }

    public function container(): DIContainer
    {
        return $this->container;
    }

    public function directories(): Directories
    {
        return $this->dirs;
    }

    public function config(): ReadOnlyConfig
    {
        if (! isset($this->read_only_config)) {
            throw new LogicException('The applications config can only be accessed after bootstrapping.');
        }

        return $this->read_only_config;
    }

    public function usesBundle(string $alias): bool
    {
        return isset($this->bundles[$alias]);
    }

    /**
     * Adds a callback that will be run after all bundles and bootstrappers have
     * been registered, but BEFORE they are bootstrapped. This is the last
     * opportunity to modify services in the container before it gets locked.
     *
     * @param callable(Kernel):void $callback
     */
    public function afterRegister(callable $callback): void
    {
        if ($this->booted) {
            throw new LogicException('register callbacks can not be added after the kernel was booted.');
        }

        $this->after_register_callbacks[] = $callback;
    }

    /**
     * Adds a callback that will be run after all configuration files have been
     * loaded from disk but BEFORE all bundles are configured. Callbacks will
     * NOT be run if the configuration is cached.
     *
     * @param callable(WritableConfig, Kernel):void $callback
     */
    public function afterConfigurationLoaded(callable $callback): void
    {
        if ($this->booted) {
            throw new LogicException('configuration callbacks can not be added after the kernel was booted.');
        }

        $this->after_config_loaded_callbacks[] = $callback;
    }

    private function loadConfiguration(): array
    {
        $loaded_config = (new ConfigLoader())($this->dirs->configDir());
        $writable_config = WritableConfig::fromArray($loaded_config);

        $writable_config->setIfMissing('kernel.bundles', []);
        $writable_config->setIfMissing('kernel.bootstrappers', []);

        foreach ($this->after_config_loaded_callbacks as $callback) {
            $callback($writable_config, $this);
        }

        $this->setBundlesAndBootstrappers(
            $writable_config->getArray('kernel.bundles'),
            $writable_config->getListOfStrings('kernel.bootstrappers')
        );

        foreach ($this->bundles as $bundle) {
            $bundle->configure($writable_config, $this);
        }

        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->configure($writable_config, $this);
        }

        $this->loaded_from_cache = false;

        return $writable_config->toArray();
    }

    private function registerBundles(): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->register($this);
        }

        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->register($this);
        }

        foreach ($this->after_register_callbacks as $callback) {
            $callback($this);
        }
    }

    private function bootBundles(): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->bootstrap($this);
        }

        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->bootstrap($this);
        }
    }

    private function configCacheFile(): string
    {
        return $this->dirs->cacheDir() . '/' . $this->env->asString() . '.config.php';
    }

    /**
     * @param array{all?: class-string<Bundle>[], prod?: class-string<Bundle>[], testing?: class-string<Bundle>[], dev?: class-string<Bundle>[]} $bundles
     *
     * @return Generator<Bundle>
     */
    private function bundlesInCurrentEnv(array $bundles): Generator
    {
        $env = $bundles[$this->env->asString()] ?? [];
        $all = $bundles[Environment::ALL] ?? [];

        foreach (array_merge($all, $env) as $bundle) {
            yield new $bundle();
        }
    }

    private function addBundle(Bundle $bundle): void
    {
        if (! $bundle->shouldRun($this->env)) {
            return;
        }

        $alias = $bundle->alias();

        if (isset($this->bundles[$alias])) {
            throw new RuntimeException(
                sprintf(
                    "2 bundles in your application share the same alias [{$alias}].\nAffected [%s]",
                    implode(',', [get_class($this->bundles[$alias]), get_class($bundle)])
                )
            );
        }

        $this->bundles[$alias] = $bundle;
    }

    /**
     * @param class-string<Bootstrapper>[] $bootstrappers
     *
     * @return Generator<Bootstrapper>
     */
    private function bootstrappersInCurrentEnv(array $bootstrappers): Generator
    {
        foreach ($bootstrappers as $class) {
            $bootstrapper = new $class();
            if ($bootstrapper->shouldRun($this->env)) {
                yield $bootstrapper;
            }
        }
    }

    private function addBootstrapper(Bootstrapper $bootstrapper): void
    {
        $this->bootstrappers[] = $bootstrapper;
    }

    private function setBundlesAndBootstrappers(array $bundles, array $bootstrappers): void
    {
        /** @var array{all?: class-string<Bundle>[], prod?: class-string<Bundle>[], testing?: class-string<Bundle>[], dev?: class-string<Bundle>[]} $bundles */
        foreach ($this->bundlesInCurrentEnv($bundles) as $bundle) {
            $this->addBundle($bundle);
        }

        /** @var class-string<Bootstrapper>[] $bootstrappers */
        foreach ($this->bootstrappersInCurrentEnv($bootstrappers) as $bootstrapper) {
            $this->addBootstrapper($bootstrapper);
        }
    }

    private function determineCache(Environment $environment): ConfigCache
    {
        if ($environment->isProduction()) {
            return new PHPFileCache();
        }

        if ($environment->isStaging()) {
            return new PHPFileCache();
        }

        return new NullCache();
    }
}
