<?php

declare(strict_types=1);

namespace Snicco\Application;

use RuntimeException;
use Snicco\Support\WpFacade;
use Snicco\Http\Psr7\Request;
use Contracts\ContainerAdapter;
use Snicco\Http\HttpServiceProvider;
use Snicco\Mail\MailServiceProvider;
use Snicco\View\ViewServiceProvider;
use Snicco\Events\EventServiceProvider;
use Snicco\Routing\RoutingServiceProvider;
use Nyholm\Psr7Server\ServerRequestCreator;
use Snicco\Factories\FactoryServiceProvider;
use Snicco\Middleware\MiddlewareServiceProvider;
use Snicco\ExceptionHandling\ExceptionServiceProvider;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class Application
{
    
    use ManagesAliases;
    use LoadsServiceProviders;
    use HasContainer;
    use SetPsrFactories;
    
    const CORE_SERVICE_PROVIDERS = [
        
        ApplicationServiceProvider::class,
        ExceptionServiceProvider::class,
        EventServiceProvider::class,
        FactoryServiceProvider::class,
        RoutingServiceProvider::class,
        HttpServiceProvider::class,
        MiddlewareServiceProvider::class,
        ViewServiceProvider::class,
        MailServiceProvider::class,
    
    ];
    
    private bool   $bootstrapped      = false;
    private Config $config;
    private bool   $running_unit_test = false;
    private string $base_path;
    
    public function __construct(ContainerAdapter $container)
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
    
    public static function create(
        string $base_path,
        ContainerAdapter $container_adapter
    ) :Application {
        
        $app = new static($container_adapter);
        $app->setBasePath($base_path);
        
        return $app;
        
    }
    
    private function setBasePath(string $base_path)
    {
        
        $this->base_path = rtrim($base_path, '\/');
    }
    
    public function boot(bool $load = true) :void
    {
        
        if ($this->bootstrapped) {
            
            throw new ConfigurationException(static::class.' already bootstrapped.');
            
        }
        
        $this->config = ((new LoadConfiguration))->bootstrap($this);
        $this->container()->instance(Config::class, $this->config);
        $this->container()->instance(ServerRequestCreator::class, $this->serverRequestCreator());
        
        if ( ! $load) {
            return;
        }
        
        $this->registerErrorHandler();
        
        $this->captureRequest();
        
        $this->loadServiceProviders();
        
        $this->bootstrapped = true;
        
    }
    
    private function registerErrorHandler()
    {
        
        /** @todo Instead of booting the error driver in the config boot it here but lazy load it from the container */
        
    }
    
    private function captureRequest()
    {
        
        $psr_request = $this->serverRequestCreator()->fromGlobals();
        
        $request = new Request($psr_request);
        
        $this->container()->instance(Request::class, $request);
        
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
        
        if ( ! $key) {
            
            return $this->config;
            
        }
        
        return $this->config->get($key, $default);
        
    }
    
    public function basePath() :string
    {
        
        return $this->base_path;
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
    
    public function runningUnitTest()
    {
        
        $this->running_unit_test = true;
    }
    
    public function isRunningUnitTest() :bool
    {
        
        return $this->running_unit_test;
    }
    
}
