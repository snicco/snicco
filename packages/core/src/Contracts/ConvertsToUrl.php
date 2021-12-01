<?php

declare(strict_types=1);

namespace Snicco\Contracts;

interface ConvertsToUrl
{
    
    /**
     * Convert to object to an url
     *
     * @param  array  $arguments
     *
     * @return string
     */
    public function toUrl(array $arguments = []) :string;
    
}
