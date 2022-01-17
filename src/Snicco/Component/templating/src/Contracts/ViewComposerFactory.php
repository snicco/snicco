<?php

declare(strict_types=1);

namespace Snicco\View\Contracts;

use Closure;
use Snicco\View\Exceptions\BadViewComposerException;

/**
 * @api
 */
interface ViewComposerFactory
{
    
    /**
     * @param  string|Closure  $composer
     *
     * @throws BadViewComposerException
     */
    public function create($composer) :ViewComposer;
    
}