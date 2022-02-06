<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel;

use Generator;
use LogicException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Snicco\Component\Kernel\Configuration\ConfigFactory;
use Snicco\Component\Kernel\Configuration\ReadOnlyConfig;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Kernel\ValueObject\PHPCacheFile;

use function array_merge;
use function get_class;
use function implode;
use function sprintf;

/**
 * @api
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class Kernel
{

    private DIContainer $container;
    private Environment $env;
    private Directories $dirs;
    private ReadOnlyConfig $read_only_config;
    private PHPCacheFile $config_cache;

    private bool $booted = false;

    /**
     * @var array<string,Bundle>
     */
    private array $bundles = [];

    /**
     * @var Bootstrapper[]
     */
    private array $bootstrappers = [];

    public function __construct(
        DIContainer $container,
        Environment $env,
        Directories $dirs
    ) {
        $this->container = $container;
        $this->env = $env;
        $this->dirs = $dirs;
        $this->config_cache = new PHPCacheFile(
            $this->dirs->cacheDir(), $this->env->asString() . '.config.php'
        );
        $this->container[ContainerInterface::class] = $this->container;
    }

    public function boot(): void
    {
        if ($this->booted) {
            throw new LogicException('The application cant be booted twice.');
        }

        $is_cached = $this->isConfigurationCached();
        $config_factory = new ConfigFactory();

        $loaded_config = $this->loadConfiguration(
            $config_factory,
            $is_cached ? $this->config_cache : null
        );

        foreach ($this->bundles($loaded_config) as $bundle) {
            $this->addBundle($bundle);
        }

        foreach ($this->bootstrappers($loaded_config) as $bootstrapper) {
            $this->addBootstrapper($bootstrapper);
        }

        if (!$is_cached) {
            $this->configureBundles($config = WritableConfig::fromArray($loaded_config));
            $this->read_only_config = ReadOnlyConfig::fromWritableConfig($config);
            $this->maybeCacheConfiguration($config_factory);
        } else {
            $this->read_only_config = ReadOnlyConfig::fromArray($loaded_config);
        }

        $this->registerBundles();
        $this->container->lock();
        $this->bootBundles();
        $this->booted = true;
    }

    public function isConfigurationCached(): bool
    {
        return $this->config_cache->isCreated();
    }

    private function loadConfiguration(ConfigFactory $config_factory, ?PHPCacheFile $cache_file = null): array
    {
        return $config_factory->load(
            $this->dirs->configDir(),
            $cache_file
        );
    }

    /**
     * @psalm-return Generator<Bundle>
     */
    private function bundles(array $configuration): Generator
    {
        /** @var array<string, list<class-string<Bundle>>> $bundles */
        $bundles = $configuration['bundles'] ?? [];

        $env = $bundles[$this->env->asString()] ?? [];
        $all = $bundles[Environment::ALL] ?? [];

        foreach (array_merge($all, $env) as $bundle) {
            yield new $bundle();
        }
    }

    private function addBundle(Bundle $bundle): void
    {
        if (!$bundle->shouldRun($this->env)) {
            return;
        }

        $alias = $bundle->alias();

        if (isset($this->bundles[$alias])) {
            throw new RuntimeException(
                sprintf(
                    "2 bundles in your application share the same alias [$alias].\nAffected [%s]",
                    implode(',', [get_class($this->bundles[$alias]), get_class($bundle)])
                )
            );
        }
        $this->bundles[$alias] = $bundle;
    }

    /**
     * @param array $loaded_config
     * @return Generator<Bootstrapper>
     */
    private function bootstrappers(array $loaded_config): Generator
    {
        /** @var list<class-string<Bootstrapper>> $boot_with */
        $boot_with = $loaded_config['app']['bootstrappers'] ?? [];
        foreach ($boot_with as $class) {
            yield new $class();
        }
    }

    private function addBootstrapper(Bootstrapper $bootstrapper): void
    {
        $this->bootstrappers[] = $bootstrapper;
    }

    private function configureBundles(WritableConfig $config): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->configure($config, $this);
        }
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->configure($config, $this);
        }
    }

    private function maybeCacheConfiguration(ConfigFactory $config_factory): void
    {
        if (!$this->env->isProduction() && !$this->env()->isStaging()) {
            return;
        }

        $config_factory->writeToCache(
            $this->config_cache->realPath(),
            $this->read_only_config->toArray()
        );
    }

    public function env(): Environment
    {
        return $this->env;
    }

    protected function registerBundles(): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->register($this);
        }
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->register($this);
        }
    }

    protected function bootBundles(): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->bootstrap($this);
        }
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->bootstrap($this);
        }
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
        if (!isset($this->read_only_config)) {
            throw new LogicException(
                'The applications config can only be accessed after bootstrapping.'
            );
        }
        return $this->read_only_config;
    }

    public function usesBundle(string $alias): bool
    {
        return isset($this->bundles[$alias]);
    }

}