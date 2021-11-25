<?php

declare(strict_types=1);

namespace Snicco\View\Contracts;

use Snicco\View\Exceptions\ViewNotFoundException;

/**
 * @api
 */
interface ViewFactory
{
    
    /**
     * Create the first view that matches the array of passed views and throw an exception if no
     * view can be created.
     *
     * @param  string[]  $views
     *
     * @return ViewInterface
     * @throws ViewNotFoundException
     */
    public function make(array $views) :ViewInterface;
    
}