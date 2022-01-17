<?php

declare(strict_types=1);

namespace Snicco\Component\Core;

use ArrayAccess;
use LogicException;
use RuntimeException;
use Psr\Container\ContainerInterface;
use Snicco\Component\Core\Utils\PHPCacheFile;
use Snicco\Component\Core\Configuration\ConfigFactory;
use Snicco\Component\Core\Configuration\Configuration;
use Snicco\Component\Core\Configuration\ReadOnlyConfig;
use Snicco\Component\Core\Configuration\WritableConfig;

use function sprintf;
use function implode;
use function get_class;

/**
 * @api
 */
class Application implements ArrayAccess
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
    
    private bool $bundles_configured = false;
    
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
    
    public function offsetExists($offset) :bool
    {
        return $this->container->offsetExists($offset);
    }
    
    /**
     * @return mixed
     */
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
        if (false === $this->bundles_configured) {
            throw new LogicException(
                "The container is not available before all bundles have been configured."
            );
        }
        return $this->container;
    }
    
    public function directories() :Directories
    {
        return $this->dirs;
    }
    
    public function boot() :void
    {
        if (true === $this->booted) {
            throw new LogicException("The application cant be booted twice.");
        }
        
        $configuration = $this->loadConfiguration();
        
        foreach ($this->bundleIterator($configuration) as $bundle) {
            $this->addBundle($bundle);
        }
        
        if ($configuration instanceof WritableConfig) {
            foreach ($this->bundles as $bundle) {
                $bundle->configure($configuration, $this);
            }
            $this->read_only_config = ReadOnlyConfig::fromWritableConfig($configuration);
        }
        elseif ($configuration instanceof ReadOnlyConfig) {
            $this->read_only_config = $configuration;
        }
        else {
            throw new RuntimeException(
                sprintf(
                    "The configuration has to be an instance of [%s] or [%s]",
                    ReadOnlyConfig::class,
                    WritableConfig::class
                )
            );
        }
        
        foreach ($this->bundles as $bundle) {
            $bundle->register($this);
        }
        
        foreach ($this->bundles as $bundle) {
            $bundle->bootstrap($this);
        }
        
        $this->booted = true;
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
    
    private function loadConfiguration() :Configuration
    {
        $cache_file = null;
        
        if ($this->env->isProduction() || $this->env()->isStaging()) {
            $cache_file = $this->config_cache;
        }
        
        return (new ConfigFactory())->create($this->dirs->configDir(), $cache_file);
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
     * @param  Configuration  $configuration
     *
     * @return Bundle[]
     */
    private function bundleIterator(Configuration $configuration) :iterable
    {
        $bundles = $configuration['bundles'] ?? [];
        foreach ($bundles as $class => $envs) {
            if ($envs[$this->env->asString()] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }
    
}