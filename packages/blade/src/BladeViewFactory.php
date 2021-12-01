<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Snicco\Support\Arr;
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
    
    public function __construct(IlluminateViewFactory $view_factory)
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
    
    /**
     * Normalize a view name.
     *
     * @param  string|string[]  $names
     *
     * @return array
     */
    private function normalizeNames($names) :array
    {
        return array_map(function ($name) {
            return ViewName::normalize($name);
        }, Arr::wrap($names));
    }
    
}