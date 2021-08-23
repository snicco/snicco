<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\WP;
use Snicco\Support\Str;
use Snicco\Support\Arr;
use Snicco\Application\Config;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Snicco\Contracts\RouteRegistrarInterface;

class RouteRegistrar implements RouteRegistrarInterface
{
    
    private Router $router;
    
    public function __construct(Router $router)
    {
        $this->router = $router;
    }
    
    public function loadIntoRouter() :void
    {
        $this->router->loadRoutes();
    }
    
    public function loadApiRoutes(Config $config) :bool
    {
        
        $files = $this->apiRoutes($config);
        
        if ( ! count($files)) {
            return false;
        }
        
        $this->requireFiles($files, $config);
        
        return true;
        
    }
    
    public function apiRoutes(Config $config) :array
    {
        
        $dirs = Arr::wrap($config->get('routing.definitions', []));
        $endpoints = Arr::wrap($config->get('routing.api.endpoints', []));
        
        $finder = new Finder();
        $finder->in($dirs)->files()
               ->name('/api\..+(?=\.php)/');
        
        return collect(iterator_to_array($finder))
            ->reject(function (SplFileInfo $file) use ($endpoints) {
                
                $name = Str::between($file->getRelativePathname(), '.', '.');
                
                return ! isset($endpoints[$name]);
                
            })
            ->all();
        
    }
    
    public function loadStandardRoutes(Config $config)
    {
        
        $dirs = Arr::wrap($config->get('routing.definitions', []));
        
        $finder = new Finder();
        $finder->in($dirs)->files()
               ->name('/^(?!api\.).+(?=\.php)/');
        
        $files = iterator_to_array($finder);
        
        if ( ! count($files)) {
            return;
        }
        
        $this->requireFiles($files, $config);
        
        $this->router->createFallbackWebRoute();
        
    }
    
    /**
     * @param  SplFileInfo[]  $files
     * @param  Config  $config
     */
    private function requireFiles(array $files, Config $config)
    {
        
        $seen = [];
        
        foreach ($files as $file) {
            
            $name = Str::before($file->getFilename(), '.php');
            
            if (isset($seen[$name])) {
                continue;
            }
            
            $preset = $config->get('routing.presets.'.$name, []);
            
            $path = $file->getRealPath();
            
            $this->loadRouteGroup($name, $path, $preset, $config);
            
            $seen[$name] = $name;
            
        }
        
    }
    
    private function loadRouteGroup(string $name, string $file_path, array $preset, Config $config)
    {
        
        $attributes = $this->applyPreset($name, $preset);
        
        $this->router->group($attributes, function ($router) use ($file_path, $config) {
            
            extract(['config' => $config]);
            require $file_path;
            
        });
        
    }
    
    private function applyPreset(string $group, array $preset) :array
    {
        
        if ($group === 'web') {
            
            return array_merge([
                'middleware' => ['web'],
            ], $preset);
            
        }
        
        if ($group === 'admin') {
            
            return array_merge([
                'middleware' => ['admin'],
                'prefix' => WP::wpAdminFolder(),
                'name' => 'admin',
            ], $preset);
            
        }
        
        if ($group === 'ajax') {
            
            return array_merge([
                'middleware' => ['ajax'],
                'prefix' => WP::wpAdminFolder().DIRECTORY_SEPARATOR.'admin-ajax.php',
                'name' => 'ajax',
            ], $preset);
            
        }
        
        return $preset;
        
    }
    
}