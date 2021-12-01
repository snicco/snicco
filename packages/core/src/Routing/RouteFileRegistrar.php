<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\Str;
use Snicco\Support\Arr;
use Snicco\Application\Config;
use Symfony\Component\Finder\Finder;
use Snicco\Contracts\RouteRegistrar;
use Symfony\Component\Finder\SplFileInfo;

class RouteFileRegistrar implements RouteRegistrar
{
    
    // Match all files that end with ".php" and don't start with an underscore.
    // https://regexr.com/691di
    const SEARCH_PATTERN = '/^[^_].+\.php$/';
    
    private Router $router;
    
    public function __construct(Router $router)
    {
        $this->router = $router;
    }
    
    public function registerRoutes(Config $config) :void
    {
        $this->registerAPIRoutes($config);
        $this->registerNormalRoutes($config);
    }
    
    /**
     * @param  SplFileInfo[]  $files
     * @param  Config  $config
     */
    private function requireFiles(array $files, Config $config)
    {
        $seen = [];
        
        $web_routes = null;
        
        foreach ($files as $file) {
            $name = Str::before($file->getFilename(), '.php');
            
            if (isset($seen[$name])) {
                continue;
            }
            
            if ($name === 'web') {
                $seen['web'] = 'web';
                $web_routes = $file->getRealPath();
                continue;
            }
            
            $preset = $config->get('routing.presets.'.$name, []);
            
            $path = $file->getRealPath();
            
            $this->requireFile($path, $preset, $config);
            
            $seen[$name] = $name;
        }
        
        if ($web_routes) {
            $preset = $config->get('routing.presets.web', []);
            
            $this->requireFile($web_routes, $preset, $config);
        }
    }
    
    private function requireFile(string $file_path, array $attributes, Config $config)
    {
        $this->router->group(function ($router) use ($file_path, $config) {
            extract(['config' => $config, 'router' => $router]);
            require $file_path;
        }, $attributes);
    }
    
    private function registerNormalRoutes(Config $config)
    {
        $dirs = Arr::wrap($config->get('routing.definitions', []));
        
        $finder = new Finder();
        $finder->in($dirs)
               ->depth(0)
               ->files()
               ->name(static::SEARCH_PATTERN);
        
        $files = iterator_to_array($finder);
        
        if ( ! count($files)) {
            return;
        }
        
        $this->requireFiles($files, $config);
    }
    
    private function registerAPIRoutes(Config $config)
    {
        $this->requireFiles($this->apiRoutes($config), $config);
    }
    
    private function apiRoutes(Config $config) :array
    {
        $api_dirs = $config->get('routing.definitions', []);
        
        $api_dirs = array_map(
            fn($dir) => rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'api',
            $api_dirs
        );
        
        $api_dirs = array_filter($api_dirs, fn($dir) => is_dir($dir));
        
        $endpoints = Arr::wrap($config->get('routing.api.endpoints', []));
        
        if ( ! count($endpoints) || ! count($api_dirs)) {
            return [];
        }
        
        $finder = new Finder();
        $finder->in($api_dirs)
               ->files()
               ->depth(0)
               ->name(static::SEARCH_PATTERN);
        
        return array_filter(
            iterator_to_array($finder),
            function (SplFileInfo $file) use ($endpoints) {
                $name = Str::before($file->getRelativePathname(), '.');
                return isset($endpoints[$name]);
            }
        );
    }
    
}