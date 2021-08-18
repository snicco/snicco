<?php

declare(strict_types=1);

namespace Snicco\View;

use Snicco\Support\FilePath;
use Symfony\Component\Finder\Finder;
use Snicco\Contracts\ViewFinderInterface;

class PhpViewFinder implements ViewFinderInterface
{
    
    /**
     * Custom views to search in. Will be searched recursively
     *
     * @param  string[]  $directories
     */
    private array  $directories;
    private string $search_depth;
    private array  $last_context = [];
    
    public function __construct(array $directories, int $depth = 2)
    {
        
        $this->search_depth = strval($depth + 1);
        $this->directories = $this->normalize($directories);
        
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
    
    public function exists(string $view_name) :bool
    {
        
        if (is_file($view_name)) {
            
            return true;
            
        }
        
        $finder = new Finder();
        $finder
            ->in($this->directories)
            ->files()
            ->depth('< '.$this->search_depth)
            ->ignoreUnreadableDirs()
            ->name(FilePath::ending($view_name, 'php'));
        
        return $finder->hasResults();
        
    }
    
    public function filePath(string $view_path) :string
    {
        
        if (is_file($view_path)) {
            
            return $view_path;
            
        }
        
        $view_path = trim($view_path, '/');
        
        $finder = new Finder();
        $finder
            ->in($this->directories)
            ->files()
            ->depth('< '.$this->search_depth)
            ->ignoreUnreadableDirs()
            ->name(FilePath::ending($view_path, 'php'));
        
        if ( ! $finder->hasResults()) {
            
            return '';
            
        }
        
        foreach ($finder as $file) {
            break;
        }
        
        return $file->getRealPath();
        
    }
    
    public function includeFile(string $path, $context)
    {
        
        $__data = array_merge($this->last_context, $context);
        $this->last_context = $__data;
        
        extract($__data, EXTR_SKIP);
        include $path;
        
    }
    
}
