<?php

declare(strict_types=1);

namespace Snicco\Application;

use ArrayAccess;
use RuntimeException;
use Snicco\Shared\ContainerAdapter;
use Snicco\Bootstrap\CaptureRequest;
use Snicco\Bootstrap\LoadConfiguration;
use Snicco\Bootstrap\DetectEnvironment;
use Snicco\Bootstrap\HandlesExceptions;
use Snicco\Bootstrap\LoadServiceProviders;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

use const PHP_SAPI;

class Application implements ArrayAccess
{
    
    use ManagesAliases;
    use HasContainer;
    use SetPsrFactories;
    
    private array $bootstrappers = [
        LoadConfiguration::class,
        DetectEnvironment::class,
        HandlesExceptions::class,
        CaptureRequest::class,
        LoadServiceProviders::class,
    ];
    
    private bool   $bootstrapped = false;
    private Config $config;
    private bool   $is_running_in_console;
    private string $base_path;
    
    private function __construct(ContainerAdapter $container, string $base_path)
    {
        $this->createImportantBindings($container);
        $this->setBasePath($base_path);
    }
    
    public static function generateKey() :string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }
    
    public static function create(string $base_path, ContainerAdapter $container_adapter) :Application
    {
        return new static($container_adapter, $base_path);
    }
    
    /**
     * @throws ConfigurationException
     */
    public function boot() :void
    {
        if ($this->hasBeenBootstrapped()) {
            throw new ConfigurationException(
                static::class.' already bootstrapped.'
            );
        }
        
        array_walk($this->bootstrappers, function ($class) {
            (new $class())->bootstrap($this);
        });
        
        $this->bootstrapped = true;
    }
    
    public function distPath(string $path = '') :string
    {
        $ds = DIRECTORY_SEPARATOR;
        $dist = $this->config('app.dist');
        $base = $this->basePath();
        $folder = rtrim($base, $ds).$ds.ltrim($dist, $ds);
        
        return $folder.($path ? $ds.ltrim($path, $ds) : $path);
    }
    
    public function config(?string $key = null, $default = null)
    {
        // Don't resolve the config from the container on every call.
        if ( ! isset($this->config)) {
            $this->config = $this[Config::class];
        }
        
        if ( ! $key) {
            return $this->config;
        }
        
        return $this->config->get($key, $default);
    }
    
    public function basePath(string $path = '') :string
    {
        return $this->base_path.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
    
    public function storagePath(string $path = '') :string
    {
        $storage_dir = $this->config->get('app.storage_dir');
        
        if ( ! $storage_dir) {
            throw new RuntimeException('No storage directory was set for the application.');
        }
        
        return $storage_dir.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
    
    public function configPath(string $path = '') :string
    {
        return $this->base_path.DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path
                : $path);
    }
    
    public function isConfigurationCached() :bool
    {
        return is_file($this->configCachePath());
    }
    
    public function hasBeenBootstrapped() :bool
    {
        return $this->bootstrapped;
    }
    
    public function configCachePath() :string
    {
        return $this->base_path
               .DIRECTORY_SEPARATOR
               .'bootstrap'
               .DIRECTORY_SEPARATOR
               .'cache'
               .DIRECTORY_SEPARATOR
               .'__generated::config.json';
    }
    
    public function isRunningUnitTest() :bool
    {
        return $this->environment() === 'testing';
    }
    
    public function environment() :string
    {
        if ( ! isset($this['env'])) {
            throw new RuntimeException(
                "App environment not set."
            );
        }
        
        $env = $this['env'];
        
        if ( ! in_array($env, ['local', 'production', 'testing'])) {
            throw new RuntimeException(
                "Allowed values for the environment are ['local','production','testing']"
            );
        }
        
        return $env;
    }
    
    public function isLocal() :bool
    {
        return $this->environment() === 'local';
    }
    
    public function isProduction() :bool
    {
        return $this->environment() === 'production';
    }
    
    public function isRunningInConsole() :bool
    {
        if ( ! isset($this->is_running_in_console)) {
            $this->is_running_in_console = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        }
        
        return $this->is_running_in_console;
    }
    
    private function setBasePath(string $base_path)
    {
        $this->base_path = rtrim($base_path, '\/');
    }
    
    private function createImportantBindings(ContainerAdapter $container) :void
    {
        $this->container_adapter = $container;
        $this->container()->instance(Application::class, $this);
        $this->container()->instance(ContainerAdapter::class, $this->container());
        $this->container()->instance(Config::class, $this->config = new Config());
    }
    
}
