<?php

declare(strict_types=1);

namespace Snicco\Contracts;

interface ViewFinder
{
    
    /**
     * Check if a view exists.
     *
     * @param  string  $view_name
     *
     * @return boolean
     */
    public function exists(string $view_name) :bool;
    
    /**
     * Return a canonical string representation of the view name.
     *
     * @param  string  $view_name
     *
     * @return string
     */
    public function filePath(string $view_name) :string;
    
}
