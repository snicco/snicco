<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Snicco\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\ViewName;
use InvalidArgumentException;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Exceptions\ViewNotFoundException;
use Illuminate\View\Factory as IlluminateViewFactory;

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
     * @throws ViewNotFoundException
     */
    public function make($views) :ViewInterface
    {
        try {
            $view = $this->view_factory->first(
                $this->normalizeNames((array) $views)
            );
            
            return new BladeView($view);
        } catch (InvalidArgumentException $e) {
            throw new ViewNotFoundException(
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