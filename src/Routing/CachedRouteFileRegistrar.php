<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\Str;
use Snicco\Application\Config;
use Snicco\Contracts\RouteRegistrar;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class CachedRouteFileRegistrar implements RouteRegistrar
{
    
    private RouteRegistrar $registrar;
    
    public function __construct(RouteFileRegistrar $registrar)
    {
        $this->registrar = $registrar;
    }
    
    public function registerRoutes(Config $config) :void
    {
        $dir = $config->get('routing.cache_dir', '');
        
        if ($this->cacheFilesCreated($dir)) {
            return;
        }
        
        if ( ! is_dir($dir)) {
            $this->createCacheDirectory($dir, $config);
        }
        
        $this->registrar->registerRoutes($config);
    }
    
    private function cacheFilesCreated($dir) :bool
    {
        return is_file($dir.DIRECTORY_SEPARATOR.'__generated:snicco_wp_route_collection');
    }
    
    private function createCacheDirectory(string $dir, Config $config)
    {
        if ( ! Str::contains($dir, $config['app.base_path'])) {
            throw new ConfigurationException(
                "The provided cache directory [$dir] has to be a child directory of the application base path."
            );
        }
        
        $created = mkdir($dir, 0755, true);
        
        if ( ! $created) {
            throw new ConfigurationException(
                "Route caching is enabled but the cache directory [$dir] could not be created."
            );
        }
    }
    
}