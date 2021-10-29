<?php

declare(strict_types=1);

namespace Snicco\View;

use Snicco\Support\Str;
use Snicco\Support\FilePath;
use Snicco\Contracts\ViewFinderInterface;

class PhpViewFinder implements ViewFinderInterface
{
    
    /**
     * Root directory roots in which we search for views.
     *
     * @param  string[]  $directories
     */
    private array $directories;
    
    private array $last_context = [];
    
    public function __construct(array $directories)
    {
        $this->directories = $this->normalize($directories);
    }
    
    public function exists(string $view_name) :bool
    {
        return file_exists($this->filePath($view_name));
    }
    
    public function filePath(string $view_name) :string
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
            
            $exists = file_exists($path);
            
            if ($exists) {
                return $path;
            }
            
        }
        
        return '';
        
    }
    
    public function includeFile(string $path, $context)
    {
        
        $__data = array_merge($this->last_context, $context);
        $this->last_context = $__data;
        
        extract($__data, EXTR_SKIP);
        include $path;
        
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
