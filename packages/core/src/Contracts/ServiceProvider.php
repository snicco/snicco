<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

use Snicco\Support\Str;
use Snicco\Support\Arr;
use Snicco\Core\Support\WP;
use Snicco\Core\DIContainer;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Application\Application_OLD;
use Snicco\Core\Configuration\WritableConfig;

abstract class ServiceProvider
{
    
    protected DIContainer $container;
    
    protected WritableConfig $config;
    
    protected Application_OLD $app;
    
    protected ?Request $current_request = null;
    
    public function __construct(DIContainer $container_adapter, WritableConfig $config)
    {
        $this->container = $container_adapter;
        $this->config = $config;
    }
    
    public function setApp(Application_OLD $app)
    {
        $this->app = $app;
    }
    
    /**
     * Register all dependencies in the IoC container.
     *
     * @return void
     */
    abstract public function register() :void;
    
    /**
     * Bootstrap any services if needed.
     *
     * @return void
     */
    abstract function bootstrap() :void;
    
    protected function validAppKey() :bool
    {
        $key = $this->appKey();
        
        if (Str::startsWith($key, $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
        }
        
        if (mb_strlen($key, '8bit') !== 32) {
            return false;
        }
        
        return true;
    }
    
    protected function appKey()
    {
        return $this->config->get('app.key', '');
    }
    
    protected function extendRoutes($routes)
    {
        $new_routes = Arr::wrap($routes);
        
        $routes = Arr::wrap($this->config->get('routing.definitions'));
        
        // new routes have to be added after the user provided routes to allow users to overwrite inbuilt routes.
        $routes = array_merge($routes, Arr::wrap($new_routes));
        
        $this->config->set('routing.definitions', $routes);
    }
    
    protected function extendViews($views)
    {
        $views = Arr::wrap($views);
        
        $old_views = $this->config->get('view.paths', []);
        $views = array_merge($old_views, $views);
        $this->config->set('view.paths', $views);
    }
    
    protected function withSlashes() :bool
    {
        $slashes = $this->config->get('routing.trailing_slash');
        
        if ($slashes === null) {
            $this->config->set('routing.trailing_slash', $slashes = WP::usesTrailingSlashes());
        }
        
        return $slashes;
    }
    
    protected function siteUrl()
    {
        return $this->config->get('app.url');
    }
    
    protected function currentRequest()
    {
        if ( ! $this->current_request instanceof Request) {
            $this->current_request = $this->container->get(Request::class);
        }
        
        return $this->current_request;
    }
    
}
