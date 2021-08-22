<?php

declare(strict_types=1);

namespace Snicco\Application;

use ArrayAccess;
use RuntimeException;
use Snicco\Support\WpFacade;
use Contracts\ContainerAdapter;
use Snicco\Bootstrap\CaptureRequest;
use Snicco\Bootstrap\DetectEnvironment;
use Snicco\Bootstrap\LoadConfiguration;
use Snicco\Bootstrap\HandlesExceptions;
use Snicco\Bootstrap\LoadServiceProviders;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

use const PHP_SAPI;

class Application implements ArrayAccess
{
    
    use ManagesAliases;
    use HasContainer;
    use SetPsrFactories;
    
    private bool   $bootstrapped      = false;
    
    private Config $config;
    
    private bool   $running_unit_test = false;
    
    private bool   $running_in_console;
    
    private string $base_path;
    
    private function __construct(ContainerAdapter $container)
    {
        
        $this->setContainer($container);
        $this->container()->instance(Application::class, $this);
        $this->container()->instance(ContainerAdapter::class, $this->container());
        WpFacade::setFacadeContainer($container);
        
    }
    
    public static function generateKey() :string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }
    
    public static function create(string $base_path, ContainerAdapter $container_adapter) :Application
    {
        $app = new static($container_adapter);
        $app->setBasePath($base_path);
        
        (new LoadConfiguration())->bootstrap($app);
        (new DetectEnvironment())->bootstrap($app);
        (new HandlesExceptions())->bootstrap($app);
        
        return $app;
    }
    
    /**
     * @throws ConfigurationException
     */
    public function boot() :void
    {
        
        if ($this->bootstrapped) {
            throw new ConfigurationException(
                static::class.' already bootstrapped.'
            );
        }
        
        (new CaptureRequest())->bootstrap($this);
        (new LoadServiceProviders())->bootstrap($this);
        
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
    
    public function storagePath($path = '') :string
    {
        
        $storage_dir = $this->config->get('app.storage_dir');
        
        if ( ! $storage_dir) {
            throw new RuntimeException('No storage directory was set for the application.');
        }
        
        return $storage_dir.($path ? DIRECTORY_SEPARATOR.$path : $path);
        
    }
    
    public function configPath($path = '') :string
    {
        
        return $this->base_path.DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path
                : $path);
    }
    
    public function isConfigurationCached() :bool
    {
        
        return is_file($this->configCachePath());
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
    
    public function runningUnitTest() :bool
    {
        return $this->isRunningUnitTest();
    }
    
    public function isRunningUnitTest() :bool
    {
        return $this['env'] === 'testing';
    }
    
    public function environment() :string
    {
        return $this['env'];
    }
    
    public function isLocal() :bool
    {
        return $this->environment() === 'local';
    }
    
    public function isProduction() :bool
    {
        return $this->environment() === 'local';
    }
    
    public function isRunningInConsole() :bool
    {
        if ($this->is_running_in_console === null) {
            $this->is_running_in_console = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        }
        
        return $this->is_running_in_console;
    }
    
    private function setBasePath(string $base_path)
    {
        
        $this->base_path = rtrim($base_path, '\/');
    }
    
}
