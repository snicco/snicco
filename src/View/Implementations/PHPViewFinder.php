<?php

declare(strict_types=1);

namespace Snicco\View\Implementations;

use Snicco\Support\Str;
use Snicco\Support\FilePath;

/**
 * @internal
 */
class PHPViewFinder
{
    
    /**
     * Root directory roots in which we search for views.
     *
     * @param  string[]  $directories
     */
    private $directories;
    
    public function __construct(array $directories = [])
    {
        $this->directories = $this->normalize($directories);
    }
    
    public function exists(string $view_name) :bool
    {
        return $this->filePath($view_name) !== null;
    }
    
    public function filePath(string $view_name) :?string
    {
        if (is_file($view_name)) {
            return $view_name;
        }
        
        $view_name = (string) Str::of($view_name)
                                 ->before('.php')
                                 ->trim('/')
                                 ->replace('.', '/');
        
        foreach ($this->directories as $directory) {
            $path = rtrim($directory, '/').'/'.$view_name.'.php';
            
            $exists = is_file($path);
            
            if ($exists) {
                return $path;
            }
        }
        
        return null;
    }
    
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
    
}
