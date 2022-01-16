<?php

declare(strict_types=1);

namespace Snicco\Component\Core;

use ArrayAccess;
use JsonException;
use LogicException;
use RuntimeException;
use Webmozart\Assert\Assert;
use Snicco\Component\Core\Utils\CacheFile;
use Psr\Container\ContainerInterface as PsrContainer;
use Snicco\Component\Core\Configuration\ConfigFactory;
use Snicco\Component\Core\Configuration\ReadOnlyConfig;
use Snicco\Component\Core\Configuration\WritableConfig;

/**
 * @api
 */
final class Application implements ArrayAccess
{
    
    private DIContainer $container;
    
    private Environment $env;
    
    private Directories $dirs;
    
    private ReadOnlyConfig $read_only_config;
    
    private bool $booted = false;
    
    /**
     * @var array<string,Plugin>
     */
    private array $plugins = [];
    
    /**
     * @param  Plugin[]  $plugins
     */
    public function __construct(
        DIContainer $container,
        Environment $env,
        Directories $dirs,
        array $plugins = []
    ) {
        $this->container = $container;
        $this->env = $env;
        $this->dirs = $dirs;
        $this->container->instance(PsrContainer::class, $this->container);
        $this->setPlugins($plugins);
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
        
        $mutable_config = $this->loadConfiguration();
        
        $this->loadPlugins($mutable_config);
        
        $this->read_only_config = ReadOnlyConfig::fromArray($mutable_config->toArray());
        
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
    
    public function hasPlugin(string $alias) :bool
    {
        return isset($this->plugins[$alias]);
    }
    
    /**
     * @throws JsonException
     */
    private function loadConfiguration() :WritableConfig
    {
        $cache_file = null;
        
        if ($this->env->isProduction() || $this->env()->isStaging()) {
            $cache_file = $this->configCacheFile();
        }
        
        return (new ConfigFactory())->create($this->dirs->configDir(), $cache_file);
    }
    
    private function configCacheFile() :CacheFile
    {
        return new CacheFile(
            $this->dirs->cacheDir(),
            $this->env->asString().'.config.json'
        );
    }
    
    private function setPlugins(array $plugins)
    {
        Assert::allIsInstanceOf($plugins, Plugin::class);
        
        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            if ( ! $plugin->runsInEnvironments($this->env)) {
                continue;
            }
            
            $alias = $plugin->alias();
            
            if (isset($this->plugins[$alias])) {
                throw new RuntimeException(
                    sprintf(
                        "2 plugins in your application share the same alias [$alias].\nAffected [%s]",
                        implode(',', [get_class($this->plugins[$alias]), get_class($plugin)])
                    )
                );
            }
            $this->plugins[$alias] = $plugin;
        }
    }
    
    private function loadPlugins(WritableConfig $writeable_config)
    {
        foreach ($this->plugins as $plugin) {
            $plugin->configure($writeable_config, $this);
        }
        
        foreach ($this->plugins as $plugin) {
            $plugin->register($this);
        }
        
        foreach ($this->plugins as $plugin) {
            $plugin->bootstrap($this);
        }
    }
    
}