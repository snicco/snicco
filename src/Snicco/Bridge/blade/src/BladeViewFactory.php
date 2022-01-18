<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\ViewName;
use InvalidArgumentException;
use Snicco\Component\Templating\View\View;
use Illuminate\View\Factory as IlluminateViewFactory;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\ViewFactory\ViewFactory;

/**
 * @internal
 */
class BladeViewFactory implements ViewFactory
{
    
    /**
     * @var IlluminateViewFactory
     */
    private $view_factory;
    
    /**
     * @var array
     */
    private $view_directories;
    
    public function __construct(IlluminateViewFactory $view_factory, array $view_directories)
    {
        $this->view_factory = $view_factory;
        $this->view_directories = $view_directories;
    }
    
    /**
     * @param  string|string[]  $views
     *
     * @return BladeView
     * @throws ViewNotFound
     */
    public function make($views) :View
    {
        try {
            $view = $this->view_factory->first(
                $this->normalizeNames((array) $views)
            );
            
            return new BladeView($view);
        } catch (InvalidArgumentException $e) {
            throw new ViewNotFound(
                'Could not find any of the views: ['
                .implode(',', Arr::wrap($views))
                .'] with the blade engine.',
                $e->getCode(),
                $e
            );
        }
    }
    
    private function normalizeNames(array $names) :array
    {
        $names = array_map(function ($path) {
            if ( ! is_file($path)) {
                return $path;
            }
            return $this->convertAbsolutePathToName($path);
        }, $names);
        
        return array_map(function ($name) {
            return ViewName::normalize($name);
        }, $names);
    }
    
    // We need to do this because Blade only supports views by name relative to one of the view directories.
    private function convertAbsolutePathToName($path) :string
    {
        foreach ($this->view_directories as $view_directory) {
            if (Str::startsWith($path, $view_directory)) {
                return (string) Str::of($path)
                                   ->after($view_directory)
                                   ->replace('/', '.')
                                   ->ltrim('.')
                                   ->before('.blade');
            }
        }
        
        return $path;
    }
    
}