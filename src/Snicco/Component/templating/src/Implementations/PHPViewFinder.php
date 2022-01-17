<?php

declare(strict_types=1);

namespace Snicco\View\Implementations;

use Snicco\View\FilePath;

class PHPViewFinder
{
    
    /**
     * directories in which we search for views.
     *
     * @param  string[]  $directories
     */
    private $directories;
    
    public function __construct(array $directories = [])
    {
        $this->directories = $this->normalize($directories);
    }
    
    /**
     * @interal
     */
    public function exists(string $view_name) :bool
    {
        return $this->filePath($view_name) !== null;
    }
    
    /**
     * @interal
     */
    public function filePath(string $view_name) :?string
    {
        if (is_file($view_name)) {
            return $view_name;
        }
        
        $view_name = $this->normalizeViewName($view_name);
        
        foreach ($this->directories as $directory) {
            $path = rtrim($directory, '/').'/'.$view_name.'.php';
            
            $exists = is_file($path);
            
            if ($exists) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * @interal
     */
    public function includeFile(string $path, array $context)
    {
        return (static function () use ($path, $context) {
            extract($context, EXTR_SKIP);
            
            unset($context);
            
            return require $path;
        })();
    }
    
    private function normalize(array $directories) :array
    {
        return array_filter(
            array_map([
                FilePath::class,
                'removeTrailingSlash',
            ], $directories)
        );
    }
    
    private function normalizeViewName(string $view_name)
    {
        $name = strstr($view_name, '.php', true);
        $name = ($name === false) ? $view_name : $name;
        
        $name = trim($name, '/');
        return str_replace('.', '/', $name);
    }
    
}
