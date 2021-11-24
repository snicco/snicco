<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Throwable;
use Snicco\Support\Arr;
use Illuminate\View\Factory;
use Illuminate\View\ViewName;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Contracts\ViewEngineInterface;
use Snicco\ExceptionHandling\Exceptions\ViewNotFoundException;

class BladeEngine implements ViewEngineInterface
{
    
    private Factory $view_factory;
    
    public function __construct(Factory $view_factory)
    {
        $this->view_factory = $view_factory;
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
                $this->normalizeNames($views)
            );
            
            return new BladeView($view);
        } catch (Throwable $e) {
            throw new ViewNotFoundException(
                'Could not render any of the views: ['
                .implode(',', Arr::wrap($views))
                .'] with the blade engine.',
                $e
            );
        }
    }
    
    /**
     * Normalize a view name.
     *
     * @param  string|string[]  $names
     *
     * @return array
     */
    private function normalizeNames($names) :array
    {
        return collect($names)->map(fn($name) => ViewName::normalize($name))->all();
    }
    
}