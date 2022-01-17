<?php

declare(strict_types=1);

namespace Snicco\Component\Core;

use ArrayAccess;
use LogicException;
use RuntimeException;
use Psr\Container\ContainerInterface;
use Snicco\Component\Core\Utils\PHPCacheFile;
use Snicco\Component\Core\Configuration\ConfigFactory;
use Snicco\Component\Core\Configuration\ReadOnlyConfig;
use Snicco\Component\Core\Configuration\WritableConfig;

use function sprintf;
use function implode;
use function get_class;

/**
 * @api
 */
final class Application implements ArrayAccess
{
    
    private DIContainer    $container;
    private Environment    $env;
    private Directories    $dirs;
    private ReadOnlyConfig $read_only_config;
    private PHPCacheFile   $config_cache;
    
    private bool $booted = false;
    
    /**
     * @var array<string,Bundle>
     */
    private array $bundles = [];
    
    /**
     * @var Bootstrapper[]
     */
    private iterable $bootstrappers = [];
    
    public function __construct(
        DIContainer $container,
        Environment $env,
        Directories $dirs
    ) {
        $this->container = $container;
        $this->env = $env;
        $this->dirs = $dirs;
        $this->config_cache = new PHPCacheFile(
            $this->dirs->cacheDir(), $this->env->asString().'.config.php'
        );
        $this->container[ContainerInterface::class] = $this->container;
    }
    
    public function boot() :void
    {
        if (true === $this->booted) {
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
        
        if ( ! $is_cached) {
            $this->configureBundles($config = WritableConfig::fromArray($loaded_config));
            $this->read_only_config = ReadOnlyConfig::fromWritableConfig($config);
            $this->maybeCacheConfiguration($config_factory);
        }
        else {
            $this->read_only_config = ReadOnlyConfig::fromArray($loaded_config);
        }
        
        $this->registerBundles();
        $this->bootBundles();
        
        $this->booted = true;
        $this->container->lock();
    }
    
    public function offsetExists($offset) :bool
    {
        return $this->container->offsetExists($offset);
    }
    
    public function offsetGet($offset)
    {
        return $this->container->offsetGet($offset);
    }
    
    public function offsetSet($offset, $value) :void
    {
        $this->container->offsetSet($offset, $value);
    }
    
    public function offsetUnset($offset) :void
    {
        $this->container->offsetUnset($offset);
    }
    
    public function env() :Environment
    {
        return $this->env;
    }
    
    public function di() :DIContainer
    {
        return $this->container;
    }
    
    public function directories() :Directories
    {
        return $this->dirs;
    }
    
    public function config() :ReadOnlyConfig
    {
        if ( ! isset($this->read_only_config)) {
            throw new LogicException(
                "The applications config can only be accessed after bootstrapping."
            );
        }
        return $this->read_only_config;
    }
    
    public function usesBundle(string $alias) :bool
    {
        return isset($this->bundles[$alias]);
    }
    
    public function isConfigurationCached() :bool
    {
        return $this->config_cache->isCreated();
    }
    
    protected function registerBundles() :void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->register($this);
        }
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->register($this);
        }
    }
    
    protected function bootBundles() :void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->bootstrap($this);
        }
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->bootstrap($this);
        }
    }
    
    private function loadConfiguration(ConfigFactory $loader, ?PHPCacheFile $cache_file = null) :array
    {
        return $loader->load(
            $this->dirs->configDir(),
            $cache_file
        );
    }
    
    private function addBundle(Bundle $bundle) :void
    {
        if ( ! $bundle->runsInEnvironments($this->env)) {
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
     * @return Bundle[]
     */
    private function bundles(array $configuration) :iterable
    {
        $bundles = $configuration['bundles'] ?? [];
        foreach ($bundles as $class => $envs) {
            if ($envs[$this->env->asString()] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }
    
    private function configureBundles(WritableConfig $config)
    {
        foreach ($this->bundles as $bundle) {
            $bundle->configure($config, $this);
        }
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->configure($config, $this);
        }
    }
    
    private function maybeCacheConfiguration(ConfigFactory $config_factory) :void
    {
        if ( ! $this->env->isProduction() && ! $this->env()->isStaging()) {
            return;
        }
        
        $config_factory->writeToCache(
            $this->config_cache->realPath(),
            $this->read_only_config->toArray()
        );
    }
    
    /**
     * @param  array  $loaded_config
     *
     * @return Bootstrapper[]
     */
    private function bootstrappers(array $loaded_config) :iterable
    {
        $boot_with = $loaded_config['app']['bootstrappers'] ?? [];
        foreach ($boot_with as $class) {
            yield new $class();
        }
    }
    
    private function addBootstrapper(Bootstrapper $bootstrapper) :void
    {
        $this->bootstrappers[] = $bootstrapper;
    }
    
}