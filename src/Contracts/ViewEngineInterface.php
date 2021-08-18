<?php

declare(strict_types=1);

namespace Snicco\Contracts;

interface ViewEngineInterface
{
    
    /**
     * Create a view instance from the first view name that exists.
     *
     * @param  string|string[]  $views
     *
     * @return ViewInterface
     */
    public function make($views) :ViewInterface;
    
}
